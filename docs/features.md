---
layout: default
title: Features
nav_order: 4
description: "Comprehensive feature list with code examples demonstrating CQRS, DDD, Value Objects, and hexagonal architecture patterns."
---

# Features Guide
{: .no_toc }

Explore the features implemented in the Hexagonal Demo with real code examples.
{: .fs-6 .fw-300 }

## Table of Contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

The Hexagonal Demo showcases modern PHP and Symfony best practices through a complete gift management system. Here's what's implemented:

---

## Domain-Driven Design (DDD)

### Entities

Pure domain entities with business logic.

#### Habitant (Resident)

**Location:** `src/Cadeau/Attribution/Domain/Model/Habitant.php`

**Features:**
- Factory methods for creation
- Value Objects for properties
- Immutable ID
- Business validation

**Code Example:**

```php
use App\Cadeau\Attribution\Domain\Model\Habitant;
use App\Cadeau\Attribution\Domain\ValueObject\Age;
use App\Cadeau\Attribution\Domain\ValueObject\Email;

// Create new resident
$habitant = Habitant::create(
    prenom: 'Sophie',
    nom: 'Martin',
    age: new Age(8),
    email: new Email('sophie.martin@example.com')
);

// Access properties
$id = $habitant->getId();           // UUID string
$fullName = $habitant->getPrenom() . ' ' . $habitant->getNom();
$age = $habitant->getAge();         // Age value object
$email = $habitant->getEmail();     // Email value object

// Reconstitute from database
$habitant = Habitant::reconstitute(
    id: 'uuid-here',
    prenom: 'Sophie',
    nom: 'Martin',
    age: new Age(8),
    email: new Email('sophie.martin@example.com')
);
```

#### Cadeau (Gift)

**Location:** `src/Cadeau/Attribution/Domain/Model/Cadeau.php`

**Features:**
- Stock management
- Business rules enforcement
- Factory pattern
- Domain validation

**Code Example:**

```php
use App\Cadeau\Attribution\Domain\Model\Cadeau;

// Create new gift
$cadeau = Cadeau::create(
    nom: 'Puzzle 3D',
    description: 'Puzzle en 3 dimensions',
    quantite: 10
);

// Stock management
$cadeau->diminuerStock(1);        // Decrease stock by 1
$cadeau->augmenterStock(5);       // Increase stock by 5

// Check availability
$isAvailable = $cadeau->isEnStock();           // true if stock > 0
$canFulfill = $cadeau->estDisponible(3);       // true if stock >= 3

// Business methods
$cadeau->changerNom('Puzzle 3D Premium');
$cadeau->modifierDescription('Puzzle en 3D avec LED');

// Validation - throws exceptions
try {
    $cadeau->diminuerStock(100);  // Throws if insufficient stock
} catch (\DomainException $e) {
    // Handle: "Stock insuffisant. Disponible: 10, Demandé: 100"
}

try {
    $cadeau->augmenterStock(5000); // Throws if exceeds max (1000)
} catch (\DomainException $e) {
    // Handle: "Le stock ne peut pas dépasser 1000"
}
```

#### Attribution (Assignment)

**Location:** `src/Cadeau/Attribution/Domain/Model/Attribution.php`

**Features:**
- Aggregate root
- Manages relationship
- Timestamp tracking

**Code Example:**

```php
use App\Cadeau\Attribution\Domain\Model\Attribution;

// Create attribution
$attribution = Attribution::create(
    habitant: $habitant,
    cadeau: $cadeau
);

// Access properties
$assignedHabitant = $attribution->getHabitant();
$assignedCadeau = $attribution->getCadeau();
$assignmentDate = $attribution->getDateAttribution(); // DateTimeImmutable

// ID is auto-generated
$attributionId = $attribution->getId();  // UUID
```

---

## Value Objects

Encapsulated, validated values that protect domain integrity.

### Age Value Object

**Location:** `src/Cadeau/Attribution/Domain/ValueObject/Age.php`

**Features:**
- Validation (0-120 years)
- Category detection
- Business logic helpers

**Code Example:**

```php
use App\Cadeau\Attribution\Domain\ValueObject\Age;

// Create age
$age = new Age(25);

// Get value
$value = $age->getValue(); // 25

// Category detection
$age->isChild();    // false (< 18)
$age->isAdult();    // true  (18-64)
$age->isSenior();   // false (>= 65)

// Get category name
$category = $age->getCategory(); // "Adulte"

// Examples for different ages
$childAge = new Age(10);
$childAge->getCategory();  // "Enfant"

$seniorAge = new Age(70);
$seniorAge->getCategory(); // "Senior"

// Validation
try {
    $invalid = new Age(150); // Throws InvalidArgumentException
} catch (\InvalidArgumentException $e) {
    // "Age must be between 0 and 120"
}

try {
    $negative = new Age(-5); // Throws InvalidArgumentException
} catch (\InvalidArgumentException $e) {
    // "Age must be between 0 and 120"
}
```

### Email Value Object

**Location:** `src/Cadeau/Attribution/Domain/ValueObject/Email.php`

**Features:**
- Email format validation
- Domain extraction
- Immutability

**Code Example:**

```php
use App\Cadeau\Attribution\Domain\ValueObject\Email;

// Create email
$email = new Email('sophie.martin@example.com');

// Get value
$address = $email->getValue(); // "sophie.martin@example.com"

// Get domain
$domain = $email->getDomain(); // "example.com"

// Get local part
$local = $email->getLocalPart(); // "sophie.martin"

// Validation
try {
    $invalid = new Email('not-an-email'); // Throws
} catch (\InvalidArgumentException $e) {
    // "Invalid email format"
}

// Case handling
$email1 = new Email('User@Example.COM');
$email1->getValue(); // "user@example.com" (normalized to lowercase)
```

### HabitantId Value Object

**Location:** `src/Cadeau/Attribution/Domain/ValueObject/HabitantId.php`

**Features:**
- UUID validation
- Type safety
- Generates new UUIDs

**Code Example:**

```php
use App\Cadeau\Attribution\Domain\ValueObject\HabitantId;

// Generate new ID
$id = HabitantId::generate();
$uuid = $id->getValue(); // "550e8400-e29b-41d4-a716-446655440000"

// From existing UUID
$existingId = new HabitantId('550e8400-e29b-41d4-a716-446655440000');

// Validation
try {
    $invalid = new HabitantId('not-a-uuid'); // Throws
} catch (\InvalidArgumentException $e) {
    // "Invalid UUID format"
}
```

---

## CQRS Pattern

Complete separation of reads (Queries) and writes (Commands).

### Commands (Write Operations)

#### AttribuerCadeauxCommand

**Purpose:** Assign a gift to a resident

**Location:** `src/Cadeau/Attribution/Application/AttribuerCadeaux/`

**Code Example:**

```php
use App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommand;
use Symfony\Component\Messenger\MessageBusInterface;

// In a controller or service
public function assignGift(
    string $habitantId,
    string $cadeauId,
    MessageBusInterface $commandBus
): void {
    $command = new AttribuerCadeauxCommand(
        habitantId: $habitantId,
        cadeauId: $cadeauId
    );

    // Dispatch command
    $commandBus->dispatch($command);
}
```

**Handler Implementation:**

```php
final class AttribuerCadeauxCommandHandler implements MessageHandlerInterface
{
    public function __construct(
        private HabitantRepositoryInterface $habitantRepository,
        private CadeauRepositoryInterface $cadeauRepository,
        private AttributionRepositoryInterface $attributionRepository,
    ) {}

    public function __invoke(AttribuerCadeauxCommand $command): void
    {
        // 1. Retrieve entities
        $habitant = $this->habitantRepository->findById($command->habitantId);
        $cadeau = $this->cadeauRepository->findById($command->cadeauId);

        // 2. Validation
        if (!$habitant) {
            throw new \DomainException("Habitant not found: {$command->habitantId}");
        }

        if (!$cadeau) {
            throw new \DomainException("Cadeau not found: {$command->cadeauId}");
        }

        if (!$cadeau->isEnStock()) {
            throw new \DomainException("Gift out of stock: {$cadeau->getNom()}");
        }

        // 3. Business logic
        $cadeau->diminuerStock(1);
        $attribution = Attribution::create($habitant, $cadeau);

        // 4. Persistence
        $this->cadeauRepository->save($cadeau);
        $this->attributionRepository->save($attribution);
    }
}
```

### Queries (Read Operations)

#### RecupererHabitantsQuery

**Purpose:** Retrieve all residents

**Location:** `src/Cadeau/Attribution/Application/RecupererHabitants/`

**Code Example:**

```php
use App\Cadeau\Attribution\Application\RecupererHabitants\RecupererHabitantsQuery;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

// In a controller
public function listResidents(MessageBusInterface $queryBus): array
{
    $query = new RecupererHabitantsQuery();

    // Dispatch query
    $envelope = $queryBus->dispatch($query);

    // Get response
    $response = $envelope->last(HandledStamp::class)->getResult();

    return $response->toArray();
}
```

**Handler Implementation:**

```php
final class RecupererHabitantsQueryHandler implements MessageHandlerInterface
{
    public function __construct(
        private HabitantRepositoryInterface $habitantRepository,
    ) {}

    public function __invoke(RecupererHabitantsQuery $query): RecupererHabitantsResponse
    {
        $habitants = $this->habitantRepository->findAll();

        return new RecupererHabitantsResponse($habitants);
    }
}
```

**Response DTO:**

```php
final readonly class RecupererHabitantsResponse
{
    public function __construct(
        /** @var Habitant[] */
        public array $habitants,
    ) {}

    public function toArray(): array
    {
        return array_map(
            fn(Habitant $h) => [
                'id' => $h->getId(),
                'prenom' => $h->getPrenom(),
                'nom' => $h->getNom(),
                'age' => $h->getAge()->getValue(),
                'ageCategory' => $h->getAge()->getCategory(),
                'email' => $h->getEmail()->getValue(),
            ],
            $this->habitants
        );
    }
}
```

#### RecupererCadeauxQuery

**Purpose:** Retrieve all gifts

**Code Example:**

```php
use App\Cadeau\Attribution\Application\RecupererCadeaux\RecupererCadeauxQuery;

$query = new RecupererCadeauxQuery();
$envelope = $queryBus->dispatch($query);
$response = $envelope->last(HandledStamp::class)->getResult();

// Response structure
$gifts = $response->toArray();
// [
//     ['id' => '...', 'nom' => 'Puzzle 3D', 'quantite' => 10, ...],
//     ...
// ]
```

#### RecupererStatistiquesQuery

**Purpose:** Retrieve dashboard statistics

**Code Example:**

```php
use App\Cadeau\Attribution\Application\RecupererStatistiques\RecupererStatistiquesQuery;

$query = new RecupererStatistiquesQuery();
$envelope = $queryBus->dispatch($query);
$stats = $envelope->last(HandledStamp::class)->getResult();

// Stats structure
$stats->totalHabitants;      // 10
$stats->totalCadeaux;         // 10
$stats->totalAttributions;    // 7
$stats->enfants;              // 4
$stats->adultes;              // 3
$stats->seniors;              // 3
```

---

## Repository Pattern

### Repository Interfaces (Ports)

Defined in the Domain layer, implemented in Infrastructure.

#### HabitantRepositoryInterface

**Location:** `src/Cadeau/Attribution/Domain/Port/HabitantRepositoryInterface.php`

**Methods:**

```php
interface HabitantRepositoryInterface
{
    // Basic CRUD
    public function save(Habitant $habitant): void;
    public function findById(string $id): ?Habitant;
    public function delete(Habitant $habitant): void;
    public function findAll(): array;

    // Business queries
    public function findByEmail(string $email): ?Habitant;
    public function existsByEmail(string $email): bool;
}
```

**Usage Example:**

```php
// Save new resident
$habitant = Habitant::create('Sophie', 'Martin', new Age(8), new Email('sophie@example.com'));
$repository->save($habitant);

// Find by ID
$found = $repository->findById($habitant->getId());

// Find all
$allResidents = $repository->findAll(); // Returns Habitant[]

// Find by email
$resident = $repository->findByEmail('sophie@example.com');

// Check existence
$exists = $repository->existsByEmail('sophie@example.com'); // true/false

// Delete
$repository->delete($habitant);
```

#### CadeauRepositoryInterface

**Location:** `src/Cadeau/Attribution/Domain/Port/CadeauRepositoryInterface.php`

**Methods:**

```php
interface CadeauRepositoryInterface
{
    // Basic CRUD
    public function save(Cadeau $cadeau): void;
    public function findById(string $id): ?Cadeau;
    public function delete(Cadeau $cadeau): void;
    public function findAll(): array;

    // Business queries
    public function findByNom(string $nom): ?Cadeau;
    public function findAllEnStock(): array;
}
```

**Usage Example:**

```php
// Save new gift
$cadeau = Cadeau::create('Puzzle 3D', 'Description', 10);
$repository->save($cadeau);

// Find by name
$puzzle = $repository->findByNom('Puzzle 3D');

// Find all in stock
$availableGifts = $repository->findAllEnStock(); // Only gifts with stock > 0

// Update stock
$cadeau->diminuerStock(1);
$repository->save($cadeau);
```

---

## Web Interface

### Dashboard (Home Page)

**Route:** `/`

**Controller:** `src/Controller/HomeController.php`

**Features:**
- Total counts (residents, gifts, attributions)
- Age distribution (children, adults, seniors)
- Statistics overview

**Code Example:**

```php
#[Route('/', name: 'app.home')]
final class HomeController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): Response
    {
        // Execute query
        $query = new RecupererStatistiquesQuery();
        $envelope = $this->queryBus->dispatch($query);
        $stats = $envelope->last(HandledStamp::class)->getResult();

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}
```

**Template:** `templates/home/index.html.twig`

### Residents List

**Route:** `/habitants`

**Controller:** `src/Cadeau/Attribution/UI/Http/Web/Controller/ListHabitantsController.php`

**Features:**
- List all residents
- Age category badges
- Email display
- Bootstrap styling

**Code Example:**

```php
#[Route('/habitants', name: 'app.habitants.list')]
final class ListHabitantsController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): Response
    {
        $query = new RecupererHabitantsQuery();
        $envelope = $this->queryBus->dispatch($query);
        $response = $envelope->last(HandledStamp::class)->getResult();

        return $this->render('cadeau/attribution/list_habitants.html.twig', [
            'habitants' => $response->habitants,
        ]);
    }
}
```

### Gifts Catalog

**Route:** `/cadeaux`

**Controller:** `src/Cadeau/Attribution/UI/Http/Web/Controller/ListCadeauxController.php`

**Features:**
- List all gifts
- Stock status badges
- Availability indicators

---

## Data Fixtures

Pre-loaded sample data for testing and demonstration.

### HabitantFixtures

**Location:** `src/DataFixtures/HabitantFixtures.php`

**Loaded Data:**

```php
// 10 residents with varied ages
- Sophie Martin (8 years, child)
- Lucas Dubois (45 years, adult)
- Marie Lefebvre (72 years, senior)
- Thomas Bernard (12 years, child)
- Emma Petit (35 years, adult)
- Nicolas Moreau (68 years, senior)
- Julie Roux (25 years, adult)
- Antoine Girard (5 years, child)
- Camille Laurent (15 years, child)
- Pierre Bonnet (55 years, adult)
```

### CadeauFixtures

**Loaded Data:**

```php
// 10 different gifts
- Puzzle 3D (10 in stock)
- Livre de recettes (5 in stock)
- Jeu de société (3 in stock)
- Écharpe en laine (8 in stock)
- Set de peinture (12 in stock)
- Robot télécommandé (4 in stock)
- Coffret thé (6 in stock)
- Ballon de football (7 in stock)
- Carnet de notes (15 in stock)
- Casque audio (2 in stock)
```

### AttributionFixtures

**Loaded Data:**

```php
// 7 pre-configured attributions
- Sophie → Puzzle 3D
- Lucas → Livre de recettes
- Marie → Jeu de société
- Thomas → Robot télécommandé
- Emma → Coffret thé
- Nicolas → Écharpe en laine
- Julie → Set de peinture
```

**Loading Fixtures:**

```bash
php bin/console doctrine:fixtures:load
```

---

## Validation & Business Rules

### Age Validation

- **Range:** 0-120 years
- **Categories:**
  - Child: 0-17
  - Adult: 18-64
  - Senior: 65+

### Email Validation

- **Format:** Standard email format (RFC 5322)
- **Normalization:** Lowercase conversion
- **Uniqueness:** One email per resident (enforced at business level)

### Stock Management

- **Min Stock:** 0 (cannot go negative)
- **Max Stock:** 1000
- **Validation:** Throws exceptions on invalid operations

**Examples:**

```php
// Valid operations
$cadeau->diminuerStock(5);     // OK if stock >= 5
$cadeau->augmenterStock(10);   // OK if stock + 10 <= 1000

// Invalid operations
$cadeau->diminuerStock(100);   // Throws if stock < 100
$cadeau->augmenterStock(2000); // Throws if stock + 2000 > 1000
```

---

## Auto-Generated Features

These features were generated by hexagonal-maker-bundle:

### ✅ Domain Layer (73% auto-generated)

- Entity structure
- Factory methods
- Value Object classes
- Repository interfaces
- Basic getters

### ✅ Application Layer (80% auto-generated)

- Command/Query classes
- Handler structure
- Response DTOs
- Dependency injection

### ✅ Infrastructure Layer (100% auto-generated)

- Doctrine repositories
- XML mappings
- Custom types for Value Objects

---

## Manual Additions

These features were added manually:

### Business Logic

- Stock increase/decrease methods
- Availability checking
- Age category detection
- Email domain extraction

### Web Interface

- Controllers
- Twig templates
- Bootstrap styling
- Navigation menu

### Data Fixtures

- Sample residents
- Sample gifts
- Sample attributions

---

## Summary

| Feature Category | Implementation Status |
|------------------|----------------------|
| **Entities** | ✅ Complete with business logic |
| **Value Objects** | ✅ Complete with validation |
| **Repositories** | ✅ Complete with custom queries |
| **CQRS** | ✅ Commands and Queries implemented |
| **Web UI** | ✅ Dashboard, lists, navigation |
| **Fixtures** | ✅ Sample data loaded |
| **Validation** | ✅ Domain and VO validation |
| **Stock Management** | ✅ Business rules enforced |

---

{: .highlight }
All features follow hexagonal architecture principles: pure domain, clear boundaries, and testable code. 95% of the core code was auto-generated with hexagonal-maker-bundle!
