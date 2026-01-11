---
layout: default
title: API Reference
nav_order: 5
description: "Complete API reference for web routes, CQRS commands, queries, and Symfony Messenger integration."
---

# API Reference
{: .no_toc }

Complete API documentation for routes, commands, and queries.
{: .fs-6 .fw-300 }

## Table of Contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Web Routes

All HTTP endpoints exposed by the application.

### Dashboard

#### GET `/`

**Description:** Display the home dashboard with statistics

**Controller:** `App\Controller\HomeController`

**Response:** HTML page with:
- Total residents count
- Total gifts count
- Total attributions count
- Age distribution (children, adults, seniors)

**Example Request:**

```bash
curl http://localhost:8000/
```

**Template:** `templates/home/index.html.twig`

**Query Used:** `RecupererStatistiquesQuery`

---

### Residents

#### GET `/habitants`

**Description:** List all residents with details

**Controller:** `App\Cadeau\Attribution\UI\Http\Web\Controller\ListHabitantsController`

**Response:** HTML table with:
- Full name (first name + last name)
- Age with category badge
- Email address

**Example Request:**

```bash
curl http://localhost:8000/habitants
```

**Template:** `templates/cadeau/attribution/list_habitants.html.twig`

**Query Used:** `RecupererHabitantsQuery`

**Sample Response Data:**

```php
[
    [
        'id' => '550e8400-e29b-41d4-a716-446655440000',
        'prenom' => 'Sophie',
        'nom' => 'Martin',
        'age' => 8,
        'ageCategory' => 'Enfant',
        'email' => 'sophie.martin@example.com'
    ],
    // ... more residents
]
```

---

### Gifts

#### GET `/cadeaux`

**Description:** Display gifts catalog with stock status

**Controller:** `App\Cadeau\Attribution\UI\Http\Web\Controller\ListCadeauxController`

**Response:** HTML cards/table with:
- Gift name
- Description
- Stock quantity
- Availability badge

**Example Request:**

```bash
curl http://localhost:8000/cadeaux
```

**Template:** `templates/cadeau/attribution/list_cadeaux.html.twig`

**Query Used:** `RecupererCadeauxQuery`

**Sample Response Data:**

```php
[
    [
        'id' => '660e8400-e29b-41d4-a716-446655440001',
        'nom' => 'Puzzle 3D',
        'description' => 'Puzzle en 3 dimensions',
        'quantite' => 10,
        'disponible' => true
    ],
    // ... more gifts
]
```

---

## CQRS API

Commands and Queries available via Symfony Messenger.

### Commands (Write Operations)

Commands modify the system state.

#### AttribuerCadeauxCommand

**Purpose:** Assign a gift to a resident

**Location:** `src/Cadeau/Attribution/Application/AttribuerCadeaux/AttribuerCadeauxCommand.php`

**Properties:**

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `habitantId` | string | Yes | UUID of the resident |
| `cadeauId` | string | Yes | UUID of the gift |

**Example Usage:**

```php
use App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommand;
use Symfony\Component\Messenger\MessageBusInterface;

class MyService
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {}

    public function assignGiftToResident(string $habitantId, string $cadeauId): void
    {
        $command = new AttribuerCadeauxCommand(
            habitantId: $habitantId,
            cadeauId: $cadeauId
        );

        $this->commandBus->dispatch($command);
    }
}
```

**Handler:** `AttribuerCadeauxCommandHandler`

**What It Does:**

1. Validates that resident exists
2. Validates that gift exists
3. Checks gift is in stock
4. Decreases gift stock by 1
5. Creates attribution record
6. Persists changes to database

**Exceptions:**

| Exception | Condition |
|-----------|-----------|
| `\DomainException` | Resident not found |
| `\DomainException` | Gift not found |
| `\DomainException` | Gift out of stock |

**Business Rules:**

- Gift stock must be > 0
- Both resident and gift must exist
- Stock is decreased automatically

---

### Queries (Read Operations)

Queries retrieve data without modifying state.

#### RecupererHabitantsQuery

**Purpose:** Retrieve all residents

**Location:** `src/Cadeau/Attribution/Application/RecupererHabitants/RecupererHabitantsQuery.php`

**Properties:** None (fetches all)

**Example Usage:**

```php
use App\Cadeau\Attribution\Application\RecupererHabitants\RecupererHabitantsQuery;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class MyService
{
    public function __construct(
        private MessageBusInterface $queryBus
    ) {}

    public function getAllResidents(): array
    {
        $query = new RecupererHabitantsQuery();

        $envelope = $this->queryBus->dispatch($query);
        $response = $envelope->last(HandledStamp::class)->getResult();

        return $response->toArray();
    }
}
```

**Handler:** `RecupererHabitantsQueryHandler`

**Response:** `RecupererHabitantsResponse`

**Response Structure:**

```php
RecupererHabitantsResponse {
    +habitants: Habitant[]  // Array of Habitant entities
}

// After calling toArray()
[
    [
        'id' => 'uuid',
        'prenom' => 'Sophie',
        'nom' => 'Martin',
        'age' => 8,
        'ageCategory' => 'Enfant',
        'email' => 'sophie.martin@example.com'
    ],
    // ...
]
```

---

#### RecupererCadeauxQuery

**Purpose:** Retrieve all gifts

**Location:** `src/Cadeau/Attribution/Application/RecupererCadeaux/RecupererCadeauxQuery.php`

**Properties:** None (fetches all)

**Example Usage:**

```php
use App\Cadeau\Attribution\Application\RecupererCadeaux\RecupererCadeauxQuery;

public function getAllGifts(): array
{
    $query = new RecupererCadeauxQuery();

    $envelope = $this->queryBus->dispatch($query);
    $response = $envelope->last(HandledStamp::class)->getResult();

    return $response->toArray();
}
```

**Handler:** `RecupererCadeauxQueryHandler`

**Response:** `RecupererCadeauxResponse`

**Response Structure:**

```php
RecupererCadeauxResponse {
    +cadeaux: Cadeau[]  // Array of Cadeau entities
}

// After calling toArray()
[
    [
        'id' => 'uuid',
        'nom' => 'Puzzle 3D',
        'description' => 'Puzzle en 3 dimensions',
        'quantite' => 10,
        'disponible' => true
    ],
    // ...
]
```

---

#### RecupererStatistiquesQuery

**Purpose:** Retrieve dashboard statistics

**Location:** `src/Cadeau/Attribution/Application/RecupererStatistiques/RecupererStatistiquesQuery.php`

**Properties:** None

**Example Usage:**

```php
use App\Cadeau\Attribution\Application\RecupererStatistiques\RecupererStatistiquesQuery;

public function getDashboardStats(): object
{
    $query = new RecupererStatistiquesQuery();

    $envelope = $this->queryBus->dispatch($query);
    $stats = $envelope->last(HandledStamp::class)->getResult();

    return $stats;
}
```

**Handler:** `RecupererStatistiquesQueryHandler`

**Response:** `RecupererStatistiquesResponse`

**Response Structure:**

```php
RecupererStatistiquesResponse {
    +totalHabitants: int      // Total residents count
    +totalCadeaux: int         // Total gifts count
    +totalAttributions: int    // Total attributions count
    +enfants: int              // Children count (age < 18)
    +adultes: int              // Adults count (18 <= age < 65)
    +seniors: int              // Seniors count (age >= 65)
}
```

**Example Response:**

```php
{
    totalHabitants: 10,
    totalCadeaux: 10,
    totalAttributions: 7,
    enfants: 4,
    adultes: 3,
    seniors: 3
}
```

---

## Repository API

Repository methods available for data access.

### HabitantRepositoryInterface

**Location:** `src/Cadeau/Attribution/Domain/Port/HabitantRepositoryInterface.php`

**Implementation:** `DoctrineHabitantRepository`

#### Methods

##### `save(Habitant $habitant): void`

**Purpose:** Persist a resident to database

**Parameters:**
- `$habitant` - Habitant entity to save

**Example:**

```php
$habitant = Habitant::create('Sophie', 'Martin', new Age(8), new Email('sophie@example.com'));
$repository->save($habitant);
```

---

##### `findById(string $id): ?Habitant`

**Purpose:** Find resident by UUID

**Parameters:**
- `$id` - UUID string

**Returns:** `Habitant` or `null` if not found

**Example:**

```php
$habitant = $repository->findById('550e8400-e29b-41d4-a716-446655440000');

if ($habitant) {
    echo $habitant->getPrenom();
}
```

---

##### `findAll(): array`

**Purpose:** Retrieve all residents

**Returns:** `Habitant[]`

**Example:**

```php
$allResidents = $repository->findAll();

foreach ($allResidents as $habitant) {
    echo $habitant->getPrenom() . ' ' . $habitant->getNom() . PHP_EOL;
}
```

---

##### `findByEmail(string $email): ?Habitant`

**Purpose:** Find resident by email address

**Parameters:**
- `$email` - Email address string

**Returns:** `Habitant` or `null`

**Example:**

```php
$habitant = $repository->findByEmail('sophie.martin@example.com');

if ($habitant) {
    echo "Found: " . $habitant->getPrenom();
}
```

**Implementation Note:** Queries the embedded `email.value` property.

---

##### `existsByEmail(string $email): bool`

**Purpose:** Check if email is already registered

**Parameters:**
- `$email` - Email address string

**Returns:** `true` if exists, `false` otherwise

**Example:**

```php
if ($repository->existsByEmail('sophie@example.com')) {
    throw new \DomainException('Email already registered');
}
```

---

##### `delete(Habitant $habitant): void`

**Purpose:** Remove resident from database

**Parameters:**
- `$habitant` - Habitant entity to delete

**Example:**

```php
$habitant = $repository->findById($id);
if ($habitant) {
    $repository->delete($habitant);
}
```

---

### CadeauRepositoryInterface

**Location:** `src/Cadeau/Attribution/Domain/Port/CadeauRepositoryInterface.php`

**Implementation:** `DoctrineCadeauRepository`

#### Methods

##### `save(Cadeau $cadeau): void`

**Purpose:** Persist a gift to database

**Example:**

```php
$cadeau = Cadeau::create('Puzzle 3D', 'Description', 10);
$repository->save($cadeau);
```

---

##### `findById(string $id): ?Cadeau`

**Purpose:** Find gift by UUID

**Returns:** `Cadeau` or `null`

---

##### `findAll(): array`

**Purpose:** Retrieve all gifts

**Returns:** `Cadeau[]`

---

##### `findByNom(string $nom): ?Cadeau`

**Purpose:** Find gift by name

**Parameters:**
- `$nom` - Gift name

**Returns:** `Cadeau` or `null`

**Example:**

```php
$puzzle = $repository->findByNom('Puzzle 3D');
```

---

##### `findAllEnStock(): array`

**Purpose:** Find all gifts with stock > 0

**Returns:** `Cadeau[]` - Only available gifts

**Example:**

```php
$availableGifts = $repository->findAllEnStock();

foreach ($availableGifts as $cadeau) {
    echo "{$cadeau->getNom()}: {$cadeau->getQuantite()} in stock\n";
}
```

**Implementation:** Uses DQL with `WHERE c.quantite > 0`

---

##### `delete(Cadeau $cadeau): void`

**Purpose:** Remove gift from database

---

### AttributionRepositoryInterface

**Location:** `src/Cadeau/Attribution/Domain/Port/AttributionRepositoryInterface.php`

**Implementation:** `DoctrineAttributionRepository`

#### Methods

##### `save(Attribution $attribution): void`

**Purpose:** Persist an attribution

**Example:**

```php
$attribution = Attribution::create($habitant, $cadeau);
$repository->save($attribution);
```

---

##### `findById(string $id): ?Attribution`

**Purpose:** Find attribution by UUID

---

##### `findAll(): array`

**Purpose:** Retrieve all attributions

**Returns:** `Attribution[]`

---

##### `delete(Attribution $attribution): void`

**Purpose:** Remove attribution

---

## Symfony Messenger Integration

### Message Buses

Two separate buses for CQRS:

#### Command Bus

**Service ID:** `messenger.bus.commands`

**Configuration:**

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            messenger.bus.commands:
                middleware:
                    - validation
                    - doctrine_transaction
```

**Usage in Controllers:**

```php
public function __construct(
    #[Autowire(service: 'messenger.bus.commands')]
    private MessageBusInterface $commandBus
) {}
```

#### Query Bus

**Service ID:** `messenger.bus.queries`

**Configuration:**

```yaml
framework:
    messenger:
        buses:
            messenger.bus.queries: ~
```

**Usage in Controllers:**

```php
public function __construct(
    #[Autowire(service: 'messenger.bus.queries')]
    private MessageBusInterface $queryBus
) {}
```

---

## Error Handling

### Domain Exceptions

All business rule violations throw `\DomainException`:

**Examples:**

```php
// Insufficient stock
throw new \DomainException('Stock insuffisant. Disponible: 10, Demandé: 15');

// Invalid age
throw new \InvalidArgumentException('Age must be between 0 and 120');

// Invalid email
throw new \InvalidArgumentException('Invalid email format');

// Entity not found
throw new \DomainException('Habitant not found: {uuid}');
```

### HTTP Error Handling

Controllers should catch and handle domain exceptions:

```php
try {
    $command = new AttribuerCadeauxCommand($habitantId, $cadeauId);
    $this->commandBus->dispatch($command);

    $this->addFlash('success', 'Gift assigned successfully');
} catch (\DomainException $e) {
    $this->addFlash('error', $e->getMessage());
}
```

---

## Complete API Examples

### Example 1: Assign Gift to Resident

```php
use App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommand;
use Symfony\Component\Messenger\MessageBusInterface;

class GiftAssignmentService
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {}

    public function assignGift(string $habitantId, string $cadeauId): void
    {
        try {
            $command = new AttribuerCadeauxCommand(
                habitantId: $habitantId,
                cadeauId: $cadeauId
            );

            $this->commandBus->dispatch($command);

        } catch (\DomainException $e) {
            // Handle: resident not found, gift not found, or out of stock
            throw new \RuntimeException("Failed to assign gift: " . $e->getMessage());
        }
    }
}
```

### Example 2: Get Dashboard Data

```php
use App\Cadeau\Attribution\Application\RecupererStatistiques\RecupererStatistiquesQuery;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class DashboardService
{
    public function __construct(
        private MessageBusInterface $queryBus
    ) {}

    public function getStats(): array
    {
        $query = new RecupererStatistiquesQuery();
        $envelope = $this->queryBus->dispatch($query);
        $stats = $envelope->last(HandledStamp::class)->getResult();

        return [
            'totals' => [
                'residents' => $stats->totalHabitants,
                'gifts' => $stats->totalCadeaux,
                'attributions' => $stats->totalAttributions,
            ],
            'ageDistribution' => [
                'children' => $stats->enfants,
                'adults' => $stats->adultes,
                'seniors' => $stats->seniors,
            ]
        ];
    }
}
```

### Example 3: Create New Resident

```php
use App\Cadeau\Attribution\Domain\Model\Habitant;
use App\Cadeau\Attribution\Domain\ValueObject\Age;
use App\Cadeau\Attribution\Domain\ValueObject\Email;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;

class ResidentService
{
    public function __construct(
        private HabitantRepositoryInterface $habitantRepository
    ) {}

    public function createResident(
        string $firstName,
        string $lastName,
        int $age,
        string $email
    ): string {
        // Check email uniqueness
        if ($this->habitantRepository->existsByEmail($email)) {
            throw new \DomainException("Email already registered: $email");
        }

        // Create resident
        $habitant = Habitant::create(
            prenom: $firstName,
            nom: $lastName,
            age: new Age($age),
            email: new Email($email)
        );

        // Save
        $this->habitantRepository->save($habitant);

        return $habitant->getId();
    }
}
```

---

## Testing the API

### Using cURL

**Get Dashboard:**

```bash
curl http://localhost:8000/
```

**Get Residents:**

```bash
curl http://localhost:8000/habitants
```

**Get Gifts:**

```bash
curl http://localhost:8000/cadeaux
```

### Using PHP

```php
// From a Symfony command or test
$kernel = new \App\Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer();

// Get query bus
$queryBus = $container->get('messenger.bus.queries');

// Execute query
$query = new RecupererHabitantsQuery();
$envelope = $queryBus->dispatch($query);
$response = $envelope->last(HandledStamp::class)->getResult();

var_dump($response->toArray());
```

---

{: .highlight }
This API follows CQRS principles strictly: **Commands** change state but return nothing, **Queries** return data but never modify state. All operations go through Symfony Messenger for consistency and testability.
