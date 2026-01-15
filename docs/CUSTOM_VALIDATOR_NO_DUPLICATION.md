# Custom Validator sans duplication - Architecture Hexagonale Pure

## 🎯 Objectif

Montrer comment créer un **Custom Validator Symfony** qui **délègue au Domain** sans dupliquer les règles métier.

---

## ❌ Problème : Duplication de la règle métier

### Anti-pattern : Règle dupliquée

```php
// ❌ DUPLICATION 1 : Dans le Domain
class Cadeau
{
    public function peutEtreAttribue(): bool
    {
        return $this->quantite > 0;  // ❌ Règle ici
    }
}

// ❌ DUPLICATION 2 : Dans le Custom Validator (Infrastructure)
class CadeauDisponibleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        $cadeau = $this->cadeauRepository->findById(new CadeauId($value));

        // ❌ Règle dupliquée ici
        if ($cadeau->getQuantite() <= 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
```

**Problème** : La règle "quantité > 0" est définie à 2 endroits. Si on change le seuil, il faut modifier 2 fichiers.

---

## ✅ Solution : Custom Validator qui DÉLÈGUE au Domain

### Principe clé

> **Le Domain est la source de vérité unique. L'Infrastructure UTILISE le Domain.**

### Implémentation

#### 1. Domain : Définit la règle métier

```php
// src/Cadeau/Attribution/Domain/Model/Cadeau.php

class Cadeau
{
    private int $quantite;

    /**
     * ✅ SINGLE SOURCE OF TRUTH
     * Business Rule: A gift can be attributed if stock is available.
     */
    public function peutEtreAttribue(): bool
    {
        return $this->quantite > 0;  // ✅ Règle définie UNE SEULE FOIS
    }

    /**
     * ✅ ATOMIC OPERATION
     * Validates and decreases stock atomically.
     */
    public function diminuerStock(): void
    {
        if (!$this->peutEtreAttribue()) {
            throw new \DomainException(
                sprintf('Cannot attribute gift "%s" - out of stock', $this->nom)
            );
        }

        $this->quantite--;
    }
}
```

#### 2. Infrastructure : Custom Constraint

```php
// src/Shared/Infrastructure/Validator/Constraint/CadeauDisponible.php

namespace App\Shared\Infrastructure\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class CadeauDisponible extends Constraint
{
    public string $message = 'Le cadeau "{{ nom }}" n\'est pas disponible (stock épuisé)';
    public string $notFoundMessage = 'Le cadeau avec l\'ID "{{ id }}" est introuvable';

    public function validatedBy(): string
    {
        return CadeauDisponibleValidator::class;
    }
}
```

#### 3. Infrastructure : Custom Validator qui DÉLÈGUE

```php
// src/Shared/Infrastructure/Validator/Constraint/CadeauDisponibleValidator.php

namespace App\Shared\Infrastructure\Validator\Constraint;

use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use App\Cadeau\Attribution\Domain\ValueObject\CadeauId;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class CadeauDisponibleValidator extends ConstraintValidator
{
    public function __construct(
        private CadeauRepositoryInterface $cadeauRepository
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CadeauDisponible) {
            throw new UnexpectedTypeException($constraint, CadeauDisponible::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        try {
            $cadeauId = new CadeauId($value);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        $cadeau = $this->cadeauRepository->findById($cadeauId);

        if (!$cadeau) {
            $this->context
                ->buildViolation($constraint->notFoundMessage)
                ->setParameter('{{ id }}', $value)
                ->addViolation();
            return;
        }

        // ✅ DÉLÈGUE AU DOMAIN - Pas de duplication !
        if (!$cadeau->peutEtreAttribue()) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ nom }}', $cadeau->getNom())
                ->addViolation();
        }
    }
}
```

#### 4. UI : Request DTO utilise le Custom Validator

```php
// src/Cadeau/Attribution/UI/Http/Request/AttribuerCadeauRequest.php

namespace App\Cadeau\Attribution\UI\Http\Request;

use App\Shared\Infrastructure\Validator\Constraint\CadeauDisponible;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AttribuerCadeauRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $habitantId,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[CadeauDisponible]  // ✅ Custom Validator (UI Layer)
        public string $cadeauId,
    ) {}

    public function toCommand(): AttribuerCadeauCommand
    {
        return new AttribuerCadeauCommand(
            habitantId: new HabitantId($this->habitantId),
            cadeauId: new CadeauId($this->cadeauId)
        );
    }
}
```

#### 5. Application : Command reste pur

```php
// src/Cadeau/Attribution/Application/Command/AttribuerCadeau/AttribuerCadeauCommand.php

final readonly class AttribuerCadeauCommand
{
    public function __construct(
        public HabitantId $habitantId,  // ✅ Pure Domain VO
        public CadeauId $cadeauId,      // ✅ Pure Domain VO
    ) {
        // ✅ Aucune annotation Symfony
        // ✅ 100% pur PHP
    }
}
```

#### 6. Application : Handler avec validation finale atomique

```php
// src/Cadeau/Attribution/Application/Command/AttribuerCadeau/AttribuerCadeauCommandHandler.php

final readonly class AttribuerCadeauCommandHandler
{
    public function __invoke(AttribuerCadeauCommand $command): void
    {
        $habitant = $this->habitantRepository->findById($command->habitantId);
        $cadeau = $this->cadeauRepository->findById($command->cadeauId);

        // ✅ VALIDATION FINALE ATOMIQUE (dans la transaction)
        // Protège contre les race conditions
        try {
            $cadeau->diminuerStock();  // ← Délègue au Domain
        } catch (\DomainException $e) {
            throw new \DomainException(
                sprintf('Cannot attribute gift "%s": %s', $cadeau->getNom(), $e->getMessage()),
                previous: $e
            );
        }

        $attribution = Attribution::createWithDetails(...);

        // ✅ Transaction atomique : stock + attribution
        $this->cadeauRepository->save($cadeau);
        $this->attributionRepository->save($attribution);
    }
}
```

#### 7. Configuration

```yaml
# config/services.yaml

services:
    # Custom Validator (Infrastructure)
    App\Shared\Infrastructure\Validator\Constraint\CadeauDisponibleValidator:
        arguments:
            $cadeauRepository: '@App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface'
        tags:
            - { name: 'validator.constraint_validator' }
```

---

## 🔄 Flow de validation à 2 niveaux

### Niveau 1 : UI (Validation préliminaire - feedback rapide)

```
HTTP Request: POST /api/attributions
{
    "habitantId": "uuid-1",
    "cadeauId": "uuid-2"
}
    ↓
Controller + #[MapRequestPayload]
    ↓
Symfony valide AttribuerCadeauRequest
    ↓
    - #[Assert\NotBlank] ✅
    - #[Assert\Uuid] ✅
    - #[CadeauDisponible] → CadeauDisponibleValidator
        ↓
        1. Charge Cadeau depuis repository
        2. Appelle $cadeau->peutEtreAttribue()  ← DÉLÈGUE au Domain
        3. Si false → ValidationFailedException
        4. Si true → continue
    ↓
Si valide → $request->toCommand()
    ↓
AttribuerCadeauCommand (pur, sans annotations)
```

**Bénéfice** : Feedback rapide (99% des cas)

### Niveau 2 : Handler (Validation finale - atomique)

```
Command Bus dispatch
    ↓
AttribuerCadeauCommandHandler
    ↓
Load Cadeau (peut avoir changé depuis validation UI)
    ↓
$cadeau->diminuerStock()  ← DÉLÈGUE au Domain
    ↓ Appelle peutEtreAttribue()
    ↓ Si false → \DomainException (rollback transaction)
    ↓ Si true → quantite--
    ↓
Save Cadeau (stock mis à jour)
Save Attribution
    ↓
Transaction commit ✅
```

**Bénéfice** : Protection contre race conditions

---

## 📊 Comparaison : Avec et sans délégation

### ❌ Sans délégation (duplication)

| Fichier | Règle métier définie |
|---------|----------------------|
| `Cadeau.php` | `return $this->quantite > 0;` |
| `CadeauDisponibleValidator.php` | `if ($cadeau->getQuantite() <= 0)` |
| `AttribuerCadeauCommandHandler.php` | `if ($cadeau->getQuantite() <= 0)` |

**Problème** : 3 endroits à modifier si la règle change.

### ✅ Avec délégation (source de vérité unique)

| Fichier | Règle métier |
|---------|--------------|
| `Cadeau.php` | `return $this->quantite > 0;` ✅ **SEUL ENDROIT** |
| `CadeauDisponibleValidator.php` | `if (!$cadeau->peutEtreAttribue())` ← Appelle Domain |
| `AttribuerCadeauCommandHandler.php` | `$cadeau->diminuerStock()` ← Appelle Domain |

**Bénéfice** : 1 seul endroit à modifier si la règle change.

---

## 🎯 Règles d'OR

### 1. Domain = Source de vérité unique

```php
// ✅ BON
class Cadeau
{
    public function peutEtreAttribue(): bool
    {
        return $this->quantite > 0;  // ✅ Règle définie ici
    }
}
```

### 2. Infrastructure = Délègue au Domain

```php
// ✅ BON
class CadeauDisponibleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        $cadeau = $this->cadeauRepository->findById(new CadeauId($value));

        // ✅ Délègue au Domain
        if (!$cadeau->peutEtreAttribue()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
```

### 3. Application = Utilise le Domain

```php
// ✅ BON
class AttribuerCadeauCommandHandler
{
    public function __invoke(AttribuerCadeauCommand $command): void
    {
        $cadeau = $this->cadeauRepository->findById($command->cadeauId);

        // ✅ Utilise le Domain
        $cadeau->diminuerStock();  // ← Appelle peutEtreAttribue() en interne

        $this->cadeauRepository->save($cadeau);
    }
}
```

### 4. JAMAIS dupliquer la règle

```php
// ❌ MAUVAIS
class CadeauDisponibleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        $cadeau = $this->cadeauRepository->findById(new CadeauId($value));

        // ❌ Règle dupliquée
        if ($cadeau->getQuantite() <= 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
```

---

## 📚 Autres exemples de Custom Validators qui délèguent

### Exemple 1 : Unicité (habitant ne peut pas recevoir 2 fois le même cadeau)

```php
// Domain
class Habitant
{
    public function aPourCadeau(CadeauId $cadeauId): bool
    {
        foreach ($this->attributions as $attribution) {
            if ($attribution->getCadeauId()->equals($cadeauId)) {
                return true;  // ✅ Règle définie ici
            }
        }
        return false;
    }
}

// Infrastructure - Custom Validator
class HabitantNaPasCeCadeauValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        // $value = AttribuerCadeauCommand

        $habitant = $this->habitantRepository->findById($value->habitantId);

        // ✅ Délègue au Domain
        if ($habitant->aPourCadeau($value->cadeauId)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
```

### Exemple 2 : Email non blacklisté

```php
// Domain
class Email
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
    }

    public function getDomain(): string
    {
        return substr(strrchr($this->value, "@"), 1);  // ✅ Logique Domain
    }
}

// Infrastructure - Custom Validator
class EmailNotBlacklistedValidator extends ConstraintValidator
{
    private const BLACKLISTED_DOMAINS = ['tempmail.com', 'throwaway.email'];

    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof Email) {
            return;
        }

        // ✅ Utilise la méthode Domain
        if (in_array($value->getDomain(), self::BLACKLISTED_DOMAINS)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
```

---

## 🎓 Conclusion

### Principe clé

> **Une règle métier = Un seul endroit (Domain)**

### Flux de dépendance

```
UI Layer
    ↓ utilise
Infrastructure Layer
    ↓ délègue à
Domain Layer  ← ✅ SOURCE DE VÉRITÉ
```

### Bénéfices

✅ **Pas de duplication** : Règle définie une seule fois
✅ **Évolutivité** : Changer la règle = 1 seul endroit
✅ **Testabilité** : Tester la règle = tester le Domain
✅ **Architecture pure** : Infrastructure dépend de Domain, pas l'inverse

---

**Date:** 2026-01-15
**Status:** ✅ Implementation Complete
