# Validation Refactoring - Architecture Hexagonale Pure

## 🎯 Objectif

Supprimer `ValidatorInterface` des **handlers** pour respecter l'architecture hexagonale pure.

---

## ❌ Problème Avant

### Anti-pattern: Validation dans les Handlers

```php
// ❌ MAUVAIS: Handler dépend de ValidatorInterface
final readonly class AttribuerCadeauCommandHandler
{
    public function __construct(
        private ValidatorInterface $validator,  // ❌ Pas sa responsabilité
        // ...
    ) {}

    public function __invoke(AttribuerCadeauCommand $command): void
    {
        $this->validator->validateOrFail($command);  // ❌ Validation dans handler

        // Business logic...
    }
}
```

**Pourquoi c'est mal:**
- ❌ Le handler fait 2 choses (validation + logique métier)
- ❌ Violation du Single Responsibility Principle
- ❌ La validation devrait être faite AVANT le handler
- ❌ Couplage inutile

---

## ✅ Solution Implémentée

### Approche 1: Value Objects auto-validants

**Pour `AttribuerCadeauCommand`** (validation simple: UUID)

#### Command avec Value Objects

```php
// ✅ BON: Command utilise des Value Objects
final readonly class AttribuerCadeauCommand
{
    public function __construct(
        public HabitantId $habitantId,  // ✅ VO auto-validant
        public CadeauId $cadeauId,      // ✅ VO auto-validant
    ) {
        // Si on arrive ici, c'est valide (sinon exception à la construction)
    }
}
```

#### Value Object CadeauId

```php
// Domain/ValueObject/CadeauId.php
final readonly class CadeauId
{
    public function __construct(private string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Cadeau ID cannot be empty');
        }

        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Invalid UUID format');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
```

#### Handler sans validation

```php
// ✅ BON: Handler concentré sur la logique métier
final readonly class AttribuerCadeauCommandHandler
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
        private HabitantRepositoryInterface $habitantRepository,
        private CadeauRepositoryInterface $cadeauRepository,
        private AttributionRepositoryInterface $attributionRepository,
        // ✅ Pas de ValidatorInterface
    ) {}

    public function __invoke(AttribuerCadeauCommand $command): void
    {
        // ✅ Pas de validation - Value Objects garantissent la validité

        // Load entities
        $habitant = $this->habitantRepository->findById($command->habitantId);
        $cadeau = $this->cadeauRepository->findById($command->cadeauId);

        // Business logic
        $attribution = Attribution::createWithDetails(...);
        $this->attributionRepository->save($attribution);
    }
}
```

#### Controller (UI Layer)

```php
// UI/Http/Controller/AttributionController.php
public function create(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    try {
        // ✅ Création du Command (validation via VOs)
        $command = new AttribuerCadeauCommand(
            habitantId: new HabitantId($data['habitantId']),
            cadeauId: new CadeauId($data['cadeauId'])
        );

        // Dispatch (déjà valide)
        $this->commandBus->dispatch($command);

        return new JsonResponse(['success' => true], 201);

    } catch (\InvalidArgumentException $e) {
        // VO validation failed
        return new JsonResponse([
            'error' => 'Invalid input',
            'message' => $e->getMessage()
        ], 400);
    }
}
```

**Avantages:**
- ✅ Impossible de créer un Command invalide
- ✅ Validation au plus tôt (construction)
- ✅ Handler pur (uniquement logique métier)
- ✅ Type-safe (HabitantId vs string)
- ✅ Testable facilement

---

### Approche 2: ValidationMiddleware

**Pour `SoumettreDemandeCadeauCommand`** (validation complexe: Email, Regex, NotBlank, Length...)

#### Middleware de validation

```php
// Infrastructure/Messenger/Middleware/ValidationMiddleware.php
final readonly class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ValidatorInterface $validator  // ✅ Symfony Validator
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        // Valider AVANT le handler
        $violations = $this->validator->validate($message);

        if (count($violations) > 0) {
            throw new ValidationFailedException($message, $violations);
        }

        // Continuer vers le handler
        return $stack->next()->handle($envelope, $stack);
    }
}
```

#### Configuration Messenger

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - validation           # ✅ Validation AVANT handler
                    - doctrine_transaction
            query.bus:
                middleware:
                    - validation
```

```yaml
# config/services.yaml
validation:
    class: App\Shared\Infrastructure\Messenger\Middleware\ValidationMiddleware
    arguments:
        $validator: '@validator'
    tags:
        - { name: 'messenger.middleware' }
```

#### Handler sans validation

```php
// ✅ BON: Handler sans ValidatorInterface
final readonly class SoumettreDemandeCadeauCommandHandler
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
        private DemandeCadeauRepositoryInterface $demandeCadeauRepository,
        // ✅ Pas de ValidatorInterface
    ) {}

    public function __invoke(SoumettreDemandeCadeauCommand $command): void
    {
        // ✅ Pas de validation - ValidationMiddleware l'a déjà fait
        // Si on arrive ici, c'est valide

        // Business logic
        $demande = DemandeCadeau::create(...);
        $this->demandeCadeauRepository->save($demande);
    }
}
```

**Avantages:**
- ✅ Validation centralisée (un seul endroit)
- ✅ Pas de duplication (tous les handlers en profitent)
- ✅ Handlers purs (uniquement logique métier)
- ✅ Utilise Symfony Validator (contraintes complexes)
- ✅ Infrastructure concern (middleware)

---

## 📊 Récapitulatif des changements

### Fichiers modifiés

#### 1. Domain - Nouveau Value Object
- ✅ `src/Cadeau/Attribution/Domain/ValueObject/CadeauId.php` (créé)

#### 2. Application - Commands mise à jour
- ✅ `src/Cadeau/Attribution/Application/Command/AttribuerCadeau/AttribuerCadeauCommand.php`
  - Utilise `HabitantId` et `CadeauId` (Value Objects)
  - Auto-validant

#### 3. Application - Handlers nettoyés
- ✅ `src/Cadeau/Attribution/Application/Command/AttribuerCadeau/AttribuerCadeauCommandHandler.php`
  - ❌ Supprimé: `ValidatorInterface $validator`
  - ❌ Supprimé: `$this->validator->validateOrFail($command)`
  - ✅ Nettoyé: Focus sur logique métier uniquement

- ✅ `src/Cadeau/Demande/Application/Command/SoumettreDemandeCadeau/SoumettreDemandeCadeauCommandHandler.php`
  - ❌ Supprimé: `ValidatorInterface $validator`
  - ❌ Supprimé: `$this->validator->validateOrFail($command)`

#### 4. Application - Validator supprimé
- ❌ `src/Cadeau/Attribution/Application/Command/AttribuerCadeau/AttribuerCadeauCommandValidator.php` (supprimé)

#### 5. Infrastructure - Middleware créé
- ✅ `src/Shared/Infrastructure/Messenger/Middleware/ValidationMiddleware.php` (créé)

#### 6. Configuration mise à jour
- ✅ `config/services.yaml`
  - Supprimé binding `AttribuerCadeauCommandValidator`
  - Ajouté service `validation` (middleware)

- ✅ `config/packages/messenger.yaml`
  - Déjà configuré avec middleware `validation`

---

## 🔄 Flow de validation

### Avant (❌ Anti-pattern)

```
HTTP Request
    ↓
Controller
    ↓ dispatch Command (pas validé)
Command Bus
    ↓
Handler
    ↓ validateOrFail() ❌ Validation ici
    ↓ Business logic
Repository
```

### Après (✅ Hexagonal pur)

#### Approche 1: Value Objects

```
HTTP Request
    ↓
Controller
    ↓ new Command(new HabitantId(), new CadeauId())
    ↓ ✅ Validation VO à la construction
    ↓ dispatch Command (déjà valide)
Command Bus
    ↓
Handler
    ↓ Business logic (pas de validation)
Repository
```

#### Approche 2: Middleware

```
HTTP Request
    ↓
Controller
    ↓ dispatch Command (pas encore validé)
Command Bus
    ↓
ValidationMiddleware
    ↓ ✅ Validation ici (Symfony Validator)
    ↓ Si invalid → ValidationFailedException
    ↓ Si valid → continue
Handler
    ↓ Business logic (pas de validation)
Repository
```

---

## 📚 Règles de validation dans l'hexagonale

### ✅ OÙ mettre la validation

| Type de validation | Où | Comment |
|-------------------|-----|---------|
| **Format simple** (UUID, non-empty) | Domain (Value Objects) | Validation à la construction |
| **Format complexe** (Email, Regex, Length) | Infrastructure (Middleware) | Symfony Validator |
| **Format HTTP** (required fields, JSON structure) | UI (Controller) | Validation avant création Command |
| **Business rules** | Domain (Entities) | Méthodes métier |

### ❌ OÙ NE PAS mettre la validation

| ❌ Endroit | Raison |
|-----------|--------|
| **Application Handlers** | Pas leur responsabilité (orchestration uniquement) |
| **Domain Entities** | Pas de Symfony Validator (rester pur) |
| **Repositories** | Trop tard (données déjà validées) |

---

## 🎯 Bénéfices de ce refactoring

### 1. Handlers plus propres

**Avant:**
```php
public function __invoke(Command $command): void
{
    $this->validator->validateOrFail($command);  // ❌ Pollution

    // Business logic (10 lignes)
}
```

**Après:**
```php
public function __invoke(Command $command): void
{
    // Business logic (10 lignes) - Clean! ✅
}
```

### 2. Séparation claire des responsabilités

- **Value Objects** → Validation de format simple
- **Middleware** → Validation de format complexe
- **Handlers** → Logique métier uniquement

### 3. Architecture hexagonale respectée

```
Domain (Pure PHP)
  ✅ Value Objects auto-validants
  ❌ Aucune dépendance Symfony

Application (Pure PHP)
  ✅ Handlers sans validation
  ❌ Aucune dépendance Symfony

Infrastructure (Symfony ici!)
  ✅ ValidationMiddleware
  ✅ Utilise Symfony Validator

UI (Symfony ici!)
  ✅ Controllers
  ✅ Création des Commands avec VOs
```

### 4. Testabilité maximale

**Tests unitaires Domain:**
```php
class CadeauIdTest extends TestCase
{
    public function testValidUuid(): void
    {
        $id = new CadeauId('550e8400-e29b-41d4-a716-446655440000');
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $id->value());
    }

    public function testInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CadeauId('invalid');
    }
}
```

**Tests intégration Application:**
```php
class AttribuerCadeauHandlerTest extends TestCase
{
    public function testHandle(): void
    {
        $handler = new AttribuerCadeauCommandHandler(
            new FakeIdGenerator(),
            new InMemoryHabitantRepository(),
            new InMemoryCadeauRepository(),
            new InMemoryAttributionRepository()
        );

        // Pas besoin de mock ValidatorInterface ✅

        $command = new AttribuerCadeauCommand(
            new HabitantId('uuid1'),
            new CadeauId('uuid2')
        );

        $handler($command);
        // Assert...
    }
}
```

---

## 🎓 Conclusion

### Principe clé

> **La validation est une préoccupation de FRONTIÈRE, pas de LOGIQUE MÉTIER**

### Où valider

1. **Au plus tôt**: Value Objects (construction)
2. **À la frontière**: Middleware (avant handlers)
3. **Jamais**: Dans les handlers

### Résultat

- ✅ Handlers 100% focalisés sur la logique métier
- ✅ Architecture hexagonale respectée
- ✅ Testabilité maximale
- ✅ Séparation claire des responsabilités

---

**Date:** 2026-01-15
**Status:** ✅ Refactoring Complete
