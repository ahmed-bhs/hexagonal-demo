---
layout: default
title: Architecture
nav_order: 3
description: "Deep dive into the hexagonal architecture of the demo application with three domain modules: Habitants, Cadeaux, and Attribution."
---

# Architecture Overview
{: .no_toc }

Understanding the hexagonal architecture implementation in this demo.
{: .fs-6 .fw-300 }

## Table of Contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## What is Hexagonal Architecture?

Hexagonal Architecture (also called **Ports & Adapters**) is an architectural pattern that:

- **Isolates business logic** from external concerns (database, web, etc.)
- **Makes the domain the center** of the application
- **Defines clear boundaries** between layers
- **Allows easy testing** without infrastructure dependencies
- **Enables flexibility** to swap implementations

### Core Principle

> "The domain doesn't know about the framework. The framework knows about the domain."

---

## The Three Layers

### 1. Domain Layer (Core)

**Location:** `src/Cadeau/Attribution/Domain/`

The heart of the application. Contains:

- **Entities** - Business objects (Habitant, Cadeau, Attribution)
- **Value Objects** - Validated values (Age, Email, HabitantId)
- **Ports** - Interfaces defining contracts (Repository interfaces)

**Characteristics:**
- Pure PHP (no Symfony, no Doctrine)
- No external dependencies
- Contains all business rules
- Fully testable without infrastructure

```
Domain/
├── Model/
│   ├── Habitant.php          # Resident entity
│   ├── Cadeau.php             # Gift entity
│   └── Attribution.php        # Attribution aggregate
│
├── ValueObject/
│   ├── HabitantId.php         # UUID value object
│   ├── Age.php                # Age with validation
│   └── Email.php              # Email with validation
│
└── Port/
    ├── HabitantRepositoryInterface.php
    ├── CadeauRepositoryInterface.php
    └── AttributionRepositoryInterface.php
```

### 2. Application Layer (Use Cases)

**Location:** `src/Cadeau/Attribution/Application/`

Orchestrates business logic. Contains:

- **Commands** - Write operations (AttribuerCadeaux)
- **Queries** - Read operations (RecupererHabitants, RecupererCadeaux)
- **Handlers** - Execute commands and queries
- **Responses** - DTOs for query results

**Characteristics:**
- Depends on Domain layer
- Implements CQRS pattern
- Uses Symfony Messenger
- Stateless operations

```
Application/
├── AttribuerCadeaux/
│   ├── AttribuerCadeauxCommand.php
│   └── AttribuerCadeauxCommandHandler.php
│
├── RecupererHabitants/
│   ├── RecupererHabitantsQuery.php
│   ├── RecupererHabitantsQueryHandler.php
│   └── RecupererHabitantsResponse.php
│
├── RecupererCadeaux/
│   ├── RecupererCadeauxQuery.php
│   ├── RecupererCadeauxQueryHandler.php
│   └── RecupererCadeauxResponse.php
│
└── RecupererStatistiques/
    ├── RecupererStatistiquesQuery.php
    ├── RecupererStatistiquesQueryHandler.php
    └── RecupererStatistiquesResponse.php
```

### 3. Infrastructure Layer (Adapters)

**Location:** `src/Cadeau/Attribution/Infrastructure/`

Implements ports with real technologies:

- **Doctrine Repositories** - Database persistence
- **Doctrine Mappings** - ORM configuration (XML)
- **Adapters** - External service integrations

**Characteristics:**
- Implements Domain port interfaces
- Uses Doctrine ORM
- Contains technical details
- Swappable implementations

```
Infrastructure/
└── Persistence/
    └── Doctrine/
        ├── DoctrineHabitantRepository.php
        ├── DoctrineCadeauRepository.php
        ├── DoctrineAttributionRepository.php
        │
        └── Mapping/
            ├── Habitant.orm.xml
            ├── Cadeau.orm.xml
            └── Attribution.orm.xml
```

### 4. UI Layer (Primary Adapters)

**Location:** `src/Cadeau/Attribution/UI/` and `src/Controller/`

Exposes the application to users:

- **Web Controllers** - HTTP endpoints
- **CLI Commands** - Console interface (if needed)
- **API Controllers** - REST/GraphQL endpoints (if needed)

**Characteristics:**
- Depends on Application layer
- Uses Symfony controllers
- Handles HTTP requests
- Renders templates

```
UI/
└── Http/
    └── Web/
        └── Controller/
            ├── ListHabitantsController.php
            └── ListCadeauxController.php

Controller/
└── HomeController.php
```

---

## The Three Domain Modules

This demo implements a complete bounded context with three interconnected modules:

### Module 1: Habitants (Residents)

**Purpose:** Manage residents who receive gifts

**Entities:**
- `Habitant` - Resident entity with first name, last name, age, email

**Value Objects:**
- `HabitantId` - UUID identifier
- `Age` - Age with validation and category helpers
- `Email` - Email with format validation

**Repository:**
- `HabitantRepositoryInterface` - Port
- `DoctrineHabitantRepository` - Doctrine adapter

**Key Features:**
- Age validation (0-120)
- Email format validation
- Category detection (child/adult/senior)
- Unique email constraint

**Sample Code:**

```php
// Creating a resident
$habitant = Habitant::create(
    prenom: 'Sophie',
    nom: 'Martin',
    age: new Age(8),
    email: new Email('sophie.martin@example.com')
);

// Age categories
$age = new Age(25);
$age->isChild();  // false
$age->isAdult();  // true
$age->isSenior(); // false
```

### Module 2: Cadeaux (Gifts)

**Purpose:** Manage gift catalog with stock

**Entities:**
- `Cadeau` - Gift entity with name, description, stock quantity

**Repository:**
- `CadeauRepositoryInterface` - Port
- `DoctrineCadeauRepository` - Doctrine adapter

**Key Features:**
- Stock management (increase/decrease)
- Availability checking
- Stock validation (max 1000)
- Business rules enforcement

**Sample Code:**

```php
// Creating a gift
$cadeau = Cadeau::create(
    nom: 'Puzzle 3D',
    description: 'Puzzle en 3 dimensions',
    quantite: 10
);

// Stock management
$cadeau->diminuerStock(1);     // Decrease by 1
$cadeau->augmenterStock(5);    // Increase by 5
$cadeau->isEnStock();          // true if stock > 0
$cadeau->estDisponible(3);     // true if stock >= 3
```

### Module 3: Attribution (Assignment)

**Purpose:** Assign gifts to residents

**Entities:**
- `Attribution` - Attribution aggregate linking Habitant and Cadeau

**Repository:**
- `AttributionRepositoryInterface` - Port
- `DoctrineAttributionRepository` - Doctrine adapter

**Key Features:**
- Links resident to gift
- Timestamp tracking
- Business rules (one gift per resident)
- Aggregate root pattern

**Sample Code:**

```php
// Creating an attribution
$attribution = Attribution::create(
    habitant: $habitant,
    cadeau: $cadeau
);

// Access related entities
$attribution->getHabitant();  // Returns Habitant
$attribution->getCadeau();    // Returns Cadeau
$attribution->getDateAttribution(); // Returns DateTimeImmutable
```

---

## Data Flow

### Command Flow (Write)

```
User Action
    ↓
Controller (UI)
    ↓
Command (Application)
    ↓
Command Handler (Application)
    ↓
Entity/Value Object (Domain)
    ↓
Repository Port (Domain)
    ↓
Repository Adapter (Infrastructure)
    ↓
Database
```

**Example: Assigning a Gift**

1. User clicks "Assign Gift"
2. Controller creates `AttribuerCadeauxCommand`
3. Dispatches to `AttribuerCadeauxCommandHandler`
4. Handler validates habitant and cadeau exist
5. Handler creates `Attribution` entity
6. Handler decreases cadeau stock
7. Handler saves via `AttributionRepository`
8. Doctrine persists to database

### Query Flow (Read)

```
User Request
    ↓
Controller (UI)
    ↓
Query (Application)
    ↓
Query Handler (Application)
    ↓
Repository Port (Domain)
    ↓
Repository Adapter (Infrastructure)
    ↓
Database
    ↓
Response DTO (Application)
    ↓
Controller (UI)
    ↓
View/Template
```

**Example: Listing Residents**

1. User visits `/habitants`
2. Controller creates `RecupererHabitantsQuery`
3. Dispatches to `RecupererHabitantsQueryHandler`
4. Handler calls `HabitantRepository::findAll()`
5. Doctrine fetches from database
6. Handler creates `RecupererHabitantsResponse`
7. Controller renders Twig template
8. HTML returned to user

---

## Dependency Rules

### The Dependency Inversion Principle

Dependencies flow **inward** toward the domain:

```
UI Layer
    ↓ (depends on)
Application Layer
    ↓ (depends on)
Domain Layer
    ↑ (defines interfaces)
Infrastructure Layer (implements interfaces)
```

**What this means:**

- **Domain Layer** - Depends on nothing
- **Application Layer** - Depends on Domain
- **Infrastructure Layer** - Depends on Domain (implements ports)
- **UI Layer** - Depends on Application

**Benefits:**

- Domain stays pure and testable
- Easy to swap infrastructure (e.g., Doctrine → MongoDB)
- Business logic isolated from framework
- Clear separation of concerns

---

## CQRS Pattern

### Commands (Writes)

**Purpose:** Change state

**Example: `AttribuerCadeauxCommand`**

```php
final readonly class AttribuerCadeauxCommand
{
    public function __construct(
        public string $habitantId,
        public string $cadeauId,
    ) {}
}
```

**Handler:**

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
        // 1. Fetch entities
        $habitant = $this->habitantRepository->findById($command->habitantId);
        $cadeau = $this->cadeauRepository->findById($command->cadeauId);

        // 2. Validate
        if (!$habitant || !$cadeau) {
            throw new \DomainException('Entity not found');
        }

        // 3. Business logic
        $cadeau->diminuerStock(1);
        $attribution = Attribution::create($habitant, $cadeau);

        // 4. Persist
        $this->cadeauRepository->save($cadeau);
        $this->attributionRepository->save($attribution);
    }
}
```

### Queries (Reads)

**Purpose:** Retrieve data

**Example: `RecupererHabitantsQuery`**

```php
final readonly class RecupererHabitantsQuery
{
    // No properties - fetch all
}
```

**Handler:**

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

**Response:**

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
                'email' => $h->getEmail()->getValue(),
            ],
            $this->habitants
        );
    }
}
```

---

## Repository Pattern

### Port (Interface)

Defined in **Domain Layer:**

```php
namespace App\Cadeau\Attribution\Domain\Port;

interface HabitantRepositoryInterface
{
    public function save(Habitant $habitant): void;
    public function findById(string $id): ?Habitant;
    public function delete(Habitant $habitant): void;
    public function findAll(): array;
    public function findByEmail(string $email): ?Habitant;
    public function existsByEmail(string $email): bool;
}
```

### Adapter (Implementation)

Implemented in **Infrastructure Layer:**

```php
namespace App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine;

final class DoctrineHabitantRepository implements HabitantRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function save(Habitant $habitant): void
    {
        $this->entityManager->persist($habitant);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Habitant
    {
        return $this->entityManager
            ->getRepository(Habitant::class)
            ->find($id);
    }

    public function findByEmail(string $email): ?Habitant
    {
        return $this->entityManager->createQueryBuilder()
            ->select('h')
            ->from(Habitant::class, 'h')
            ->where('h.email.value = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // ... other methods
}
```

---

## Value Objects

### Why Value Objects?

Value Objects encapsulate validation and behavior for primitive values:

- **Validation** - Ensures values are always valid
- **Immutability** - Once created, cannot change
- **Self-documentation** - Type hints show intent
- **Business logic** - Contains domain rules

### Example: Age Value Object

```php
final readonly class Age
{
    private const MIN_AGE = 0;
    private const MAX_AGE = 120;
    private const ADULT_AGE = 18;
    private const SENIOR_AGE = 65;

    public function __construct(
        private int $value,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->value < self::MIN_AGE || $this->value > self::MAX_AGE) {
            throw new \InvalidArgumentException(
                sprintf('Age must be between %d and %d', self::MIN_AGE, self::MAX_AGE)
            );
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isChild(): bool
    {
        return $this->value < self::ADULT_AGE;
    }

    public function isAdult(): bool
    {
        return $this->value >= self::ADULT_AGE && $this->value < self::SENIOR_AGE;
    }

    public function isSenior(): bool
    {
        return $this->value >= self::SENIOR_AGE;
    }

    public function getCategory(): string
    {
        return match (true) {
            $this->isChild() => 'Enfant',
            $this->isSenior() => 'Senior',
            default => 'Adulte',
        };
    }
}
```

**Benefits:**
- Can't create invalid age (< 0 or > 120)
- Category logic in one place
- Type-safe usage
- Reusable across application

---

## Factory Pattern

All entities use factory methods for creation:

### Why Factory Methods?

- **Encapsulate creation** logic
- **Generate IDs** automatically
- **Enforce invariants** at creation time
- **Separate creation** from reconstitution (from DB)

### Example: Cadeau Entity

```php
final class Cadeau
{
    private function __construct(
        private string $id,
        private string $nom,
        private string $description,
        private int $quantite,
    ) {
        $this->validate();
    }

    // Factory for NEW entities
    public static function create(
        string $nom,
        string $description,
        int $quantite,
    ): self {
        return new self(
            id: Uuid::v4()->toRfc4122(),
            nom: $nom,
            description: $description,
            quantite: $quantite,
        );
    }

    // Factory for EXISTING entities (from DB)
    public static function reconstitute(
        string $id,
        string $nom,
        string $description,
        int $quantite,
    ): self {
        return new self($id, $nom, $description, $quantite);
    }
}
```

**Usage:**

```php
// Creating new gift (generates UUID)
$gift = Cadeau::create('Puzzle', 'Puzzle 3D', 10);

// Reconstituting from database
$gift = Cadeau::reconstitute($id, $nom, $description, $quantite);
```

---

## Configuration

### Service Registration

**File:** `config/services.yaml`

```yaml
services:
    # Repositories
    App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface:
        class: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\DoctrineHabitantRepository

    App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface:
        class: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\DoctrineCadeauRepository

    # Handlers are auto-registered by Symfony Messenger
```

### Doctrine Mapping

**File:** `Infrastructure/Persistence/Doctrine/Mapping/Habitant.orm.xml`

```xml
<entity name="App\Cadeau\Attribution\Domain\Model\Habitant">
    <id name="id" type="string" />

    <field name="prenom" type="string" />
    <field name="nom" type="string" />

    <!-- Embedded Value Objects -->
    <embedded name="age" class="App\Cadeau\Attribution\Domain\ValueObject\Age" />
    <embedded name="email" class="App\Cadeau\Attribution\Domain\ValueObject\Email" />
</entity>
```

---

## Testing Strategy

### Unit Tests (Domain)

Test entities and value objects in isolation:

```php
class AgeTest extends TestCase
{
    public function testValidAge(): void
    {
        $age = new Age(25);
        $this->assertEquals(25, $age->getValue());
        $this->assertTrue($age->isAdult());
    }

    public function testInvalidAge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Age(150);
    }
}
```

### Integration Tests (Application)

Test handlers with real repositories:

```php
class AttribuerCadeauxCommandHandlerTest extends KernelTestCase
{
    public function testHandle(): void
    {
        $handler = static::getContainer()->get(AttribuerCadeauxCommandHandler::class);

        $command = new AttribuerCadeauxCommand(
            habitantId: '...',
            cadeauId: '...',
        );

        $handler($command);

        // Assert attribution created
    }
}
```

---

## Key Takeaways

1. **Domain is Pure** - No framework dependencies in domain layer
2. **Ports & Adapters** - Domain defines interfaces, infrastructure implements
3. **CQRS** - Commands change state, queries read state
4. **Value Objects** - Validate and encapsulate primitive values
5. **Factory Pattern** - Control entity creation
6. **Dependency Inversion** - Dependencies point inward to domain
7. **Testability** - Each layer testable in isolation

---

{: .highlight }
This architecture makes the code **maintainable**, **testable**, and **flexible**. The domain is protected from framework changes, and business logic is clearly separated from technical concerns.
