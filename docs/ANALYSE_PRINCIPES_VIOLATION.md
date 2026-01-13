# Analyse des Violations de Principes

## Résumé Exécutif

Cette analyse identifie les violations des principes **YAGNI**, **DRY**, **SoC**, et **SOLID** dans le projet hexagonal-demo.

**Statistiques** :
- **YAGNI** : 6 violations majeures, ~200 lignes de code inutilisé
- **DRY** : 8 violations majeures, ~300 lignes de code dupliqué
- **SoC** : 2 violations
- **SOLID** : 4 violations (S, I, O, D)

**Potentiel de réduction** : ~500 lignes de code peuvent être supprimées ou dédupliquées.

---

## 1. Violations YAGNI (You Aren't Gonna Need It)

### 1.1 Méthodes métier inutilisées dans les entités Domain

**Principe** : YAGNI - Sur-ingénierie avec fonctionnalités inutilisées

**Fichiers concernés** :
- `src/Cadeau/Attribution/Domain/Model/Cadeau.php:154-175`
- `src/Cadeau/Attribution/Domain/Model/Habitant.php:100-108`

**Code problématique** :
```php
// Cadeau.php - JAMAIS appelées
public function changerNom(string $nouveauNom): void { ... }
public function modifierDescription(string $nouvelleDescription): void { ... }
public function diminuerStock(int $quantite): void { ... }
public function augmenterStock(int $quantite): void { ... }

// Habitant.php - JAMAIS appelées
public function changeEmail(Email $newEmail): void { ... }
public function celebrerAnniversaire(): void { ... }
```

**Pourquoi c'est une violation** :
- Ces méthodes ne sont **JAMAIS appelées** dans le code applicatif
- Elles n'existent que dans la documentation et les tests
- Pas de use case implémenté pour ces opérations
- Ajoutent ~100 lignes de code inutile
- Logique de validation dupliquée (voir DRY-2.4)

**Recommandation** :
```php
// ❌ Supprimer ces méthodes jusqu'à ce qu'elles soient réellement nécessaires
// ✅ Quand nécessaire, implémenter des use cases appropriés :
//    - ChangerNomCadeauCommand
//    - ModifierDescriptionCadeauCommand
//    - GererStockCadeauCommand
//    - ModifierEmailHabitantCommand
```

**Impact** : ~100 lignes supprimées, maintenance simplifiée

---

### 1.2 Méthodes factory inutilisées dans Value Objects

**Fichiers concernés** :
- `src/Cadeau/Attribution/Domain/ValueObject/Email.php:30`
- `src/Cadeau/Attribution/Domain/ValueObject/Age.php:30`
- `src/Cadeau/Attribution/Domain/ValueObject/HabitantId.php:33`

**Code problématique** :
```php
// JAMAIS utilisées (grep = 0 usages)
public static function fromString(string $value): self
{
    return new self($value);
}

public static function fromInt(int $value): self
{
    return new self($value);
}
```

**Recommandation** :
```php
// ❌ Supprimer ces méthodes factory
// ✅ Utiliser directement : new Email($value) au lieu de Email::fromString($value)
```

**Impact** : 12 lignes supprimées

---

### 1.3 Méthodes de parsing Email inutilisées

**Fichier** : `src/Cadeau/Attribution/Domain/ValueObject/Email.php:40-50`

**Code problématique** :
```php
// JAMAIS utilisées
public function getDomain(): string { ... }
public function getLocalPart(): string { ... }
```

**Recommandation** :
```php
// ❌ Supprimer jusqu'à besoin réel (whitelist/blacklist, anonymisation)
```

**Impact** : 12 lignes supprimées

---

### 1.4 Méthodes reconstitute() inutilisées

**Fichiers concernés** :
- `src/Cadeau/Attribution/Domain/Model/Cadeau.php:75-82`
- `src/Cadeau/Demande/Domain/Model/DemandeCadeau.php:75-89`

**Code problématique** :
```php
// JAMAIS appelées (Doctrine utilise la réflexion)
public static function reconstitute(...): self { ... }
```

**Recommandation** :
```php
// ❌ Supprimer - Doctrine hydrate automatiquement via mapping YAML
```

**Impact** : 20 lignes supprimées

---

## 2. Violations DRY (Don't Repeat Yourself)

### 2.1 ⭐ CRITIQUE : IdGeneratorInterface dupliqué

**Principe** : DRY - Duplication de code entre contextes

**Fichiers** :
- `src/Cadeau/Attribution/Domain/Port/IdGeneratorInterface.php` (36 lignes)
- `src/Cadeau/Demande/Domain/Port/IdGeneratorInterface.php` (36 lignes)

**Code dupliqué** : **72 lignes identiques à 100%**

**Recommandation** :
```php
// ✅ Déplacer vers Shared Kernel
// src/Shared/Domain/Port/IdGeneratorInterface.php

namespace App\Shared\Domain\Port;

interface IdGeneratorInterface
{
    public function generate(): string;
}

// Mettre à jour tous les usages :
// - App\Cadeau\Attribution\Domain\Port\IdGeneratorInterface
//   → App\Shared\Domain\Port\IdGeneratorInterface
// - App\Cadeau\Demande\Domain\Port\IdGeneratorInterface
//   → App\Shared\Domain\Port\IdGeneratorInterface
```

**Impact** : 72 lignes supprimées (36 × 2)

---

### 2.2 ⭐ CRITIQUE : UuidV7Generator dupliqué

**Fichiers** :
- `src/Cadeau/Attribution/Infrastructure/Generator/UuidV7Generator.php` (51 lignes)
- `src/Cadeau/Demande/Infrastructure/Generator/UuidV7Generator.php` (51 lignes)

**Code dupliqué** : **102 lignes identiques à 100%**

**Recommandation** :
```php
// ✅ Déplacer vers Shared Infrastructure
// src/Shared/Infrastructure/Generator/UuidV7Generator.php

namespace App\Shared\Infrastructure\Generator;

use App\Shared\Domain\Port\IdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final readonly class UuidV7Generator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}

// Mettre à jour config/services.yaml :
App\Shared\Domain\Port\IdGeneratorInterface:
    class: App\Shared\Infrastructure\Generator\UuidV7Generator
```

**Impact** : 102 lignes supprimées (51 × 2)

---

### 2.3 Méthode equals() dupliquée dans 7 Value Objects

**Fichiers concernés** (7 fichiers) :
- Age, Email, HabitantId, Page, PerPage, Total, SearchTerm

**Code dupliqué** : **21 lignes** (7 × 3 lignes)

```php
// Pattern identique dans 7 fichiers
public function equals(self $other): bool
{
    return $this->value === $other->value;
}
```

**Recommandation** :
```php
// Option 1 (préférée) : ❌ Supprimer equals() - JAMAIS utilisée
// PHP supporte === pour comparaison d'objets readonly

// Option 2 : ✅ Trait si vraiment nécessaire
namespace App\Shared\Domain\ValueObject;

trait ValueObjectEquality
{
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

**Impact** : 21 lignes supprimées

---

### 2.4 Validation du nom dupliquée dans Cadeau

**Fichier** : `src/Cadeau/Attribution/Domain/Model/Cadeau.php`

**Lignes** : 36-46 (constructeur) et 154-168 (changerNom) - **22 lignes dupliquées**

**Problèmes** :
- Validation identique à 2 endroits
- Messages d'erreur incohérents (anglais vs français)
- Risque de divergence

**Recommandation** :
```php
// ✅ Extraire un Value Object NomCadeau
namespace App\Cadeau\Attribution\Domain\ValueObject;

final readonly class NomCadeau
{
    public function __construct(public string $value)
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            throw new \InvalidArgumentException('Gift name cannot be empty');
        }

        if (strlen($trimmed) < 3 || strlen($trimmed) > 100) {
            throw new \InvalidArgumentException(
                'Gift name must be between 3 and 100 characters'
            );
        }

        $this->value = $trimmed;
    }
}

// Utilisation dans Cadeau
class Cadeau
{
    private NomCadeau $nom;

    public static function create(
        string $id,
        string $nom,
        string $description,
        int $quantite
    ): self {
        return new self(
            $id,
            new NomCadeau($nom),  // Validation déléguée
            $description,
            $quantite
        );
    }
}
```

**Impact** : 22 lignes dupliquées éliminées, validation centralisée

---

### 2.5 Validation email dupliquée

**Fichiers** :
- `src/Cadeau/Attribution/Domain/ValueObject/Email.php:25`
- `src/Cadeau/Demande/Domain/Model/DemandeCadeau.php:62`

**Code dupliqué** :
```php
// Email.php (Value Object)
if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
    throw new \InvalidArgumentException(sprintf('Invalid email format: "%s"', $value));
}

// DemandeCadeau.php (Entity) - DUPLICATION
if (!filter_var($emailDemandeur, FILTER_VALIDATE_EMAIL)) {
    throw new \InvalidArgumentException('Email invalide');
}
```

**Recommandation** : Voir SoC-3.1 (déplacer Email dans Shared)

---

### 2.6 Logique de pagination dupliquée

**Fichier** : `src/Cadeau/Attribution/Infrastructure/Persistence/Doctrine/DoctrineHabitantRepository.php`

**Lignes** : 73-90 (findPaginated) et 93-117 (searchPaginated) - **30+ lignes dupliquées**

**Recommandation** :
```php
// ✅ Extraire une méthode privée paginate()
private function paginate(
    QueryBuilder $qb,
    Page $page,
    PerPage $perPage
): PaginatedResult {
    $qb->setFirstResult(($page->toInt() - 1) * $perPage->toInt())
       ->setMaxResults($perPage->toInt());

    $paginator = new Paginator($qb->getQuery());
    $total = new Total(count($paginator));

    return new PaginatedResult(
        items: iterator_to_array($paginator),
        currentPage: $page,
        perPage: $perPage,
        total: $total
    );
}

public function findPaginated(Page $page, PerPage $perPage): PaginatedResult
{
    $qb = $this->createQueryBuilder('h')
        ->orderBy('h.nom', 'ASC');

    return $this->paginate($qb, $page, $perPage);
}
```

**Impact** : 30 lignes dupliquées éliminées

---

### 2.7 Méthodes CRUD dupliquées dans 4 repositories

**Fichiers concernés** :
- DoctrineCadeauRepository
- DoctrineHabitantRepository
- DoctrineAttributionRepository
- DoctrineDemandeCadeauRepository

**Code dupliqué** : **~48 lignes** identiques

```php
// IDENTIQUE dans 4 repositories
public function save(Entity $entity): void
{
    $this->entityManager->persist($entity);
    $this->entityManager->flush();
}

public function findById(string $id): ?Entity
{
    return $this->entityManager->find(Entity::class, $id);
}

public function delete(Entity $entity): void
{
    $this->entityManager->remove($entity);
    $this->entityManager->flush();
}

public function findAll(): array
{
    return $this->entityManager->getRepository(Entity::class)->findAll();
}
```

**Recommandation** :
```php
// ✅ Créer un AbstractDoctrineRepository
namespace App\Shared\Infrastructure\Persistence\Doctrine;

abstract class AbstractDoctrineRepository
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {}

    abstract protected function getEntityClass(): string;

    protected function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function findById(string $id): ?object
    {
        return $this->entityManager->find($this->getEntityClass(), $id);
    }

    protected function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    protected function findAll(): array
    {
        return $this->entityManager
            ->getRepository($this->getEntityClass())
            ->findAll();
    }
}

// Usage
final class DoctrineCadeauRepository
    extends AbstractDoctrineRepository
    implements CadeauRepositoryInterface
{
    protected function getEntityClass(): string
    {
        return Cadeau::class;
    }

    // Seulement les méthodes spécifiques
    public function findByNom(string $nom): ?Cadeau { ... }
    public function findAllEnStock(): array { ... }
}
```

**Impact** : 48 lignes dupliquées éliminées

---

## 3. Violations SoC (Separation of Concerns)

### 3.1 ⭐ DemandeCadeau avec string au lieu d'Email VO

**Principe** : SoC - Logique Domain dans une entité

**Fichier** : `src/Cadeau/Demande/Domain/Model/DemandeCadeau.php:26, 62-64`

**Code problématique** :
```php
class DemandeCadeau
{
    private string $emailDemandeur;  // ❌ Devrait être Email VO

    public static function create(..., string $emailDemandeur, ...): self
    {
        // ❌ Validation email dupliquée ici au lieu d'utiliser Email VO
        if (!filter_var($emailDemandeur, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        return new self(..., $emailDemandeur, ...);
    }
}
```

**Problèmes** :
- Validation email mélangée dans DemandeCadeau
- **Inconsistant** avec Habitant (qui utilise Email VO)
- Email VO existe déjà dans Attribution mais pas réutilisé
- Viole SoC : validation email n'est pas la responsabilité de DemandeCadeau

**Recommandation** :
```php
// ✅ 1. Déplacer Email vers Shared Kernel
// src/Shared/Domain/ValueObject/Email.php

namespace App\Shared\Domain\ValueObject;

final readonly class Email
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email format: "%s"', $value)
            );
        }
    }
}

// ✅ 2. Utiliser Email VO dans DemandeCadeau
use App\Shared\Domain\ValueObject\Email;

class DemandeCadeau
{
    private Email $emailDemandeur;  // ✅ Email VO

    public static function create(..., string $emailDemandeur, ...): self
    {
        // ✅ Validation déléguée à Email VO
        $email = new Email($emailDemandeur);

        return new self(..., $email, ...);
    }
}
```

**Impact** : Validation centralisée, cohérence entre contextes

---

### 3.2 Application layer créant des Value Objects

**Fichier** : `src/Cadeau/Attribution/Application/RecupererHabitants/RecupererHabitantsQueryHandler.php:29-31`

**Code problématique** :
```php
public function __invoke(RecupererHabitantsQuery $query): RecupererHabitantsResponse
{
    // ❌ Application layer transformant primitives → VOs
    $page = new Page($query->page);
    $perPage = new PerPage($query->perPage);
    $searchTerm = new SearchTerm($query->searchTerm);

    $result = $this->habitantRepository->searchPaginated($searchTerm, $page, $perPage);
}
```

**Problème** :
- **Inconsistant** avec autres handlers (RecupererCadeaux, RecupererStatistiques)
- Application handler fait de la transformation
- Viole SoC : transformation devrait être au niveau Controller/Query

**Recommandation** :
```php
// ✅ Option 1 : Query contient déjà des VOs
final readonly class RecupererHabitantsQuery
{
    public function __construct(
        public Page $page,
        public PerPage $perPage,
        public SearchTerm $searchTerm,
    ) {}
}

// Handler devient plus simple
public function __invoke(RecupererHabitantsQuery $query): RecupererHabitantsResponse
{
    $result = $this->habitantRepository->searchPaginated(
        $query->searchTerm,
        $query->page,
        $query->perPage
    );
    // ...
}

// ✅ Option 2 : Controller crée les VOs
// (approche actuelle est acceptable aussi)
```

**Note** : Cette violation est débattable. Certains préfèrent des primitives dans les Queries pour la simplicité.

---

## 4. Violations SOLID

### 4.1 Single Responsibility : Entité Cadeau fait trop de choses

**Principe** : SOLID-S (Single Responsibility)

**Fichier** : `src/Cadeau/Attribution/Domain/Model/Cadeau.php` (176 lignes)

**Responsabilités identifiées** (9 au total) :
1. Identité & Données
2. Création & Reconstitution
3. Validation nom (constructeur)
4. Validation quantité (constructeur)
5. Gestion stock
6. Requêtes stock
7. Mutation nom
8. Validation nom (changerNom) - DUPLICATION
9. Mutation description

**Recommandation** :
```php
// ✅ Extraire des Value Objects

// 1. NomCadeau VO (validation centralisée)
final readonly class NomCadeau
{
    public function __construct(public string $value)
    {
        $trimmed = trim($value);

        if (empty($trimmed) || strlen($trimmed) < 3 || strlen($trimmed) > 100) {
            throw new \InvalidArgumentException(
                'Gift name must be between 3 and 100 characters'
            );
        }

        $this->value = $trimmed;
    }
}

// 2. Quantite VO (logique métier stock)
final readonly class Quantite
{
    public function __construct(public int $value)
    {
        if ($value < 0 || $value > 1000) {
            throw new \InvalidArgumentException(
                'Quantity must be between 0 and 1000'
            );
        }
    }

    public function isEnStock(): bool
    {
        return $this->value > 0;
    }

    public function estDisponible(int $quantiteDemandee): bool
    {
        return $this->value >= $quantiteDemandee;
    }
}

// 3. Cadeau simplifié
class Cadeau
{
    private string $id;
    private NomCadeau $nom;
    private string $description;
    private Quantite $quantite;

    private function __construct(
        string $id,
        NomCadeau $nom,
        string $description,
        Quantite $quantite
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->description = trim($description);
        $this->quantite = $quantite;
    }

    public static function create(
        string $id,
        string $nom,
        string $description,
        int $quantite
    ): self {
        return new self(
            $id,
            new NomCadeau($nom),
            $description,
            new Quantite($quantite)
        );
    }

    // ❌ Supprimer : reconstitute, changerNom, modifierDescription,
    // diminuerStock, augmenterStock (YAGNI)
}
```

**Impact** : Entité passe de 176 lignes à ~50 lignes, responsabilités claires

---

### 4.2 Interface Segregation : Repository interfaces trop larges

**Principe** : SOLID-I (Interface Segregation)

**Fichier** : `src/Cadeau/Attribution/Domain/Port/CadeauRepositoryInterface.php`

**Problème** :
```php
// ❌ Interface mixe Write et Read
interface CadeauRepositoryInterface
{
    public function save(Cadeau $cadeau): void;       // Write
    public function delete(Cadeau $cadeau): void;     // Write

    public function findById(string $id): ?Cadeau;    // Read
    public function findAll(): array;                 // Read
    public function findByNom(string $nom): ?Cadeau;  // Read
    public function findAllEnStock(): array;          // Read
}
```

**Problèmes** :
- Viole Command-Query Separation (CQS)
- Clients read-only forcés de dépendre de méthodes write
- Même problème dans HabitantRepositoryInterface (8 méthodes)

**Recommandation** :
```php
// ✅ Séparer Read et Write

// Write interface (Commands)
interface CadeauWriteRepositoryInterface
{
    public function save(Cadeau $cadeau): void;
    public function delete(Cadeau $cadeau): void;
}

// Read interface (Queries)
interface CadeauReadRepositoryInterface
{
    public function findById(string $id): ?Cadeau;
    public function findByNom(string $nom): ?Cadeau;
    public function findAll(): array;
    public function findAllEnStock(): array;
}

// Implementation
final class DoctrineCadeauRepository implements
    CadeauWriteRepositoryInterface,
    CadeauReadRepositoryInterface
{
    // ... implementation
}

// Command handlers dépendent seulement de Write
class AttribuerCadeauxCommandHandler
{
    public function __construct(
        private CadeauReadRepositoryInterface $cadeauReadRepository,
        private AttributionWriteRepositoryInterface $attributionWriteRepository,
    ) {}
}

// Query handlers dépendent seulement de Read
class RecupererCadeauxQueryHandler
{
    public function __construct(
        private CadeauReadRepositoryInterface $cadeauReadRepository,
    ) {}
}
```

**Impact** : Dépendances explicites, meilleure testabilité

---

### 4.3 Open/Closed : Catégories d'âge en dur

**Principe** : SOLID-O (Open/Closed)

**Fichier** : `src/Cadeau/Attribution/Domain/ValueObject/Age.php:40-53`

**Code** :
```php
// ❌ Seuils hard-codés
public function isAdult(): bool
{
    return $this->value >= 18;  // Hard-coded
}

public function isSenior(): bool
{
    return $this->value >= 65;  // Hard-coded
}
```

**Problème** :
- Seuils **hard-codés** (18, 65)
- Impossible d'étendre sans modifier la classe
- Pays différents = seuils différents
- Impossible d'ajouter nouvelles catégories (teen, young adult)

**Recommandation** :
```php
// ✅ Option 1 : Service Domain avec configuration
final readonly class AgeCategoryService
{
    public function __construct(
        private int $adultThreshold = 18,
        private int $seniorThreshold = 65,
    ) {}

    public function isChild(Age $age): bool
    {
        return $age->value < $this->adultThreshold;
    }

    public function isAdult(Age $age): bool
    {
        return $age->value >= $this->adultThreshold
            && $age->value < $this->seniorThreshold;
    }

    public function isSenior(Age $age): bool
    {
        return $age->value >= $this->seniorThreshold;
    }
}

// ✅ Option 2 : Garder tel quel (PRAGMATIQUE)
// Les catégories d'âge sont des invariants Domain stables
// Pas de besoin métier de configuration
```

**Recommandation** : **Violation mineure**. L'approche actuelle est pragmatique et acceptable. Refactoriser seulement si besoin métier de seuils configurables.

---

### 4.4 Dependency Inversion : Controller connaît HandlerFailedException

**Principe** : SOLID-D (Dependency Inversion) - En fait, plutôt SoC

**Fichier** : `src/Cadeau/Attribution/UI/Http/Web/Controller/ListHabitantsController.php:105-123`

**Code problématique** :
```php
try {
    $query = new RecupererHabitantsQuery(...);
    $envelope = $this->queryBus->dispatch($query);
    // ...
} catch (HandlerFailedException $e) {  // ❌ Controller connaît Messenger
    $originalException = $e->getPrevious();

    if ($originalException instanceof \InvalidArgumentException) {
        $errors[] = $originalException->getMessage();
    } else {
        $errors[] = 'Une erreur est survenue...';
    }
}
```

**Problèmes** :
- Controller dépend des détails Symfony Messenger
- **Inconsistant** avec ListCadeauxController (pas de gestion d'erreur)
- UI layer ne devrait pas connaître infrastructure exceptions

**Recommandation** :
```php
// ✅ Option 1 : EventSubscriber pour exceptions (recommandé)
// src/UI/Http/Web/EventSubscriber/ExceptionSubscriber.php

final class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Unwrap HandlerFailedException
        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious();
        }

        // Convertir domain exceptions → HTTP responses
        // ...
    }
}

// ✅ Option 2 : Controller simplifié (laisser erreurs remonter)
public function __invoke(Request $request): Response
{
    $query = new RecupererHabitantsQuery(
        page: $request->query->getInt('page', 1),
        perPage: $request->query->getInt('per_page', 10),
        searchTerm: $request->query->getString('search', '')
    );

    $envelope = $this->queryBus->dispatch($query);
    $response = $envelope->last(HandledStamp::class)->getResult();

    return $this->render('cadeau/attribution/list_habitants.html.twig', [
        'response' => $response,
    ]);
}
```

**Impact** : Controller simplifié, gestion d'erreur centralisée

---

## 5. Problèmes Additionnels

### 5.1 Nomenclature repository inconsistante

**Problème** :
- La plupart : `findById(string $id)`
- DemandeCadeauRepositoryInterface : `find(string $id)` (différent)

**Recommandation** : Standardiser à `findById()` partout

---

### 5.2 Méthodes toString() inutilisées

**Fichiers concernés** : Age, Email, HabitantId, SearchTerm

**Code** :
```php
// ❌ toString() JAMAIS appelée (seulement __toString() est magique)
public function toString(): string
{
    return $this->value;
}

public function __toString(): string
{
    return $this->value;
}
```

**Recommandation** :
```php
// ✅ Garder seulement __toString() (méthode magique)
public function __toString(): string
{
    return $this->value;
}

// Ou mieux : value est déjà public readonly
// Accès direct via $email->value au lieu de $email->toString()
```

**Impact** : 16 lignes supprimées

---

## Recommandations Priorisées

### 🔴 Haute Priorité (Faire en premier)

1. **DRY-2.1 & 2.2** : Déplacer IdGeneratorInterface + UuidV7Generator vers Shared
   - **Impact** : ~174 lignes supprimées
   - **Effort** : 30 minutes

2. **YAGNI-1.1, 1.2, 1.6** : Supprimer méthodes inutilisées des entités
   - **Impact** : ~100 lignes supprimées
   - **Effort** : 15 minutes

3. **SoC-3.1 & DRY-2.5** : Email VO dans Shared + utilisation dans DemandeCadeau
   - **Impact** : Cohérence entre contextes
   - **Effort** : 30 minutes

4. **DRY-2.6** : Extraire helper pagination dans DoctrineHabitantRepository
   - **Impact** : ~30 lignes supprimées
   - **Effort** : 20 minutes

### 🟡 Moyenne Priorité

5. **DRY-2.3** : Supprimer equals() ou utiliser trait
   - **Impact** : ~21 lignes supprimées
   - **Effort** : 10 minutes

6. **SOLID-S** : Extraire NomCadeau et Quantite VOs
   - **Impact** : Cadeau passe de 176 → 50 lignes
   - **Effort** : 1 heure

7. **YAGNI-1.3, 1.4, 1.5** : Supprimer fromString(), getDomain(), reconstitute()
   - **Impact** : ~50 lignes supprimées
   - **Effort** : 15 minutes

8. **DRY-2.4** : Extraire NomCadeau VO (élimine duplication validation)
   - **Impact** : ~22 lignes dupliquées supprimées
   - **Effort** : Inclus dans #6

### 🟢 Basse Priorité (Nice to have)

9. **SOLID-I** : Séparer repository interfaces Read/Write
   - **Impact** : Meilleure testabilité
   - **Effort** : 2 heures

10. **DRY-2.7** : AbstractDoctrineRepository pour CRUD
    - **Impact** : ~48 lignes supprimées
    - **Effort** : 1 heure

11. **Issue-5.2** : Supprimer toString() inutilisées
    - **Impact** : ~16 lignes supprimées
    - **Effort** : 5 minutes

12. **SOLID-O** : Rendre catégories âge configurables
    - **Impact** : Seulement si besoin métier
    - **Effort** : 1 heure

---

## Métriques

**Réduction de code potentielle** :
- **YAGNI** : ~200 lignes
- **DRY** : ~300 lignes
- **Total** : ~500 lignes (réduction ~15% du codebase)

**Temps estimé** :
- **Haute priorité** : 1h35
- **Moyenne priorité** : 2h25
- **Basse priorité** : 4h05
- **Total** : ~8 heures

**Impact maintenabilité** : **ÉLEVÉ**
- Moins de duplication = moins de bugs
- Code plus clair = meilleure compréhension
- Responsabilités séparées = meilleure testabilité

---

## Conclusion

Le projet hexagonal-demo est globalement **bien architecturé** (100% hexagonal pur), mais souffre de :

1. **Sur-ingénierie** (YAGNI) : Méthodes inutilisées ajoutées "au cas où"
2. **Duplication** (DRY) : Code répété entre contextes (IdGenerator, Email, etc.)
3. **Responsabilités floues** (SoC/SOLID-S) : Entités trop larges

**Prochaines étapes recommandées** :

1. ✅ Appliquer les **4 actions haute priorité** (1h35) → Impact immédiat
2. ✅ Créer des **Value Objects** (NomCadeau, Quantite, Email partagé) → Cohérence
3. ✅ Nettoyer **code mort** (méthodes inutilisées) → Maintenabilité

Avec ces changements, le projet sera **exemplaire** en termes de principes de design ! 🎉
