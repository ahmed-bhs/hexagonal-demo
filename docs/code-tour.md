---
layout: default
title: Code Tour
nav_order: 6
description: "Interactive tour comparing auto-generated code vs manual additions, showcasing the power of hexagonal-maker-bundle."
---

# Code Tour: Generated vs Manual
{: .no_toc }

Take a guided tour through the codebase to see what was generated automatically vs what was written manually.
{: .fs-6 .fw-300 }

## Table of Contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

This demo showcases the power of the **hexagonal-maker-bundle**. Let's explore what was generated and what required manual coding.

### Quick Stats

| Layer | Auto-Generated | Manual | % Auto |
|-------|---------------|--------|--------|
| **Domain** | ~400 lines | ~150 lines | 73% |
| **Application** | ~200 lines | ~50 lines | 80% |
| **Infrastructure** | ~250 lines | 0 lines | 100% |
| **UI/Fixtures** | 0 lines | ~550 lines | 0% |
| **Total Core** | ~850 lines | ~200 lines | 81% |

{: .note }
The "Total Core" excludes UI and Fixtures, which are demo-specific. In a real application using the bundle, you'd achieve similar 80%+ generation rates.

---

## 1. Domain Layer

### Habitant Entity

**File:** `src/Cadeau/Attribution/Domain/Model/Habitant.php`

#### ✅ Auto-Generated (by hexagonal-maker-bundle)

```php
final class Habitant
{
    // ✅ Generated: Constructor with private visibility
    private function __construct(
        private string $id,
        private string $prenom,
        private string $nom,
        private Age $age,
        private Email $email,
    ) {
        $this->validate();
    }

    // ✅ Generated: Factory method for new entities
    public static function create(
        string $prenom,
        string $nom,
        Age $age,
        Email $email,
    ): self {
        return new self(
            id: \Symfony\Component\Uid\Uuid::v4()->toRfc4122(),
            prenom: $prenom,
            nom: $nom,
            age: $age,
            email: $email,
        );
    }

    // ✅ Generated: Factory method for existing entities (from DB)
    public static function reconstitute(
        string $id,
        string $prenom,
        string $nom,
        Age $age,
        Email $email,
    ): self {
        return new self($id, $prenom, $nom, $age, $email);
    }

    // ✅ Generated: All getters
    public function getId(): string { return $this->id; }
    public function getPrenom(): string { return $this->prenom; }
    public function getNom(): string { return $this->nom; }
    public function getAge(): Age { return $this->age; }
    public function getEmail(): Email { return $this->email; }

    // ✅ Generated: Basic validation structure
    private function validate(): void
    {
        if (empty($this->prenom)) {
            throw new \InvalidArgumentException('First name cannot be empty');
        }
        if (empty($this->nom)) {
            throw new \InvalidArgumentException('Last name cannot be empty');
        }
    }
}
```

**Generated:** ~80% of the file

**Why it's valuable:**
- Factory pattern enforced
- UUID generation automatic
- Type safety guaranteed
- Consistent structure

---

### Cadeau Entity

**File:** `src/Cadeau/Attribution/Domain/Model/Cadeau.php`

#### ✅ Auto-Generated

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

    public static function create(
        string $nom,
        string $description,
        int $quantite,
    ): self {
        return new self(
            id: \Symfony\Component\Uid\Uuid::v4()->toRfc4122(),
            nom: $nom,
            description: $description,
            quantite: $quantite,
        );
    }

    public static function reconstitute(
        string $id,
        string $nom,
        string $description,
        int $quantite,
    ): self {
        return new self($id, $nom, $description, $quantite);
    }

    // Getters...
    public function getId(): string { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getDescription(): string { return $this->description; }
    public function getQuantite(): int { return $this->quantite; }
}
```

#### ❌ Added Manually (Business Logic)

```php
final class Cadeau
{
    // ... generated code above ...

    // ❌ Manual: Stock decrease with validation
    public function diminuerStock(int $quantite): void
    {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException('La quantité à diminuer doit être positive');
        }

        if ($this->quantite < $quantite) {
            throw new \DomainException(sprintf(
                'Stock insuffisant. Disponible: %d, Demandé: %d',
                $this->quantite,
                $quantite
            ));
        }

        $this->quantite -= $quantite;
    }

    // ❌ Manual: Stock increase with limit
    public function augmenterStock(int $quantite): void
    {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException('La quantité à ajouter doit être positive');
        }

        $newQuantite = $this->quantite + $quantite;

        if ($newQuantite > 1000) {
            throw new \DomainException(sprintf(
                'Le stock ne peut pas dépasser 1000. Stock actuel: %d, Quantité à ajouter: %d',
                $this->quantite,
                $quantite
            ));
        }

        $this->quantite = $newQuantite;
    }

    // ❌ Manual: Availability checks
    public function isEnStock(): bool
    {
        return $this->quantite > 0;
    }

    public function estDisponible(int $quantiteDemandee): bool
    {
        return $this->quantite >= $quantiteDemandee;
    }

    // ❌ Manual: Name change with validation
    public function changerNom(string $nouveauNom): void
    {
        $nouveauNom = trim($nouveauNom);

        if (empty($nouveauNom)) {
            throw new \InvalidArgumentException('Le nom du cadeau ne peut pas être vide');
        }

        if (strlen($nouveauNom) < 3) {
            throw new \InvalidArgumentException('Le nom doit contenir au moins 3 caractères');
        }

        if (strlen($nouveauNom) > 100) {
            throw new \InvalidArgumentException('Le nom ne peut pas dépasser 100 caractères');
        }

        $this->nom = $nouveauNom;
    }

    // ❌ Manual: Description modification
    public function modifierDescription(string $nouvelleDescription): void
    {
        $this->description = trim($nouvelleDescription);
    }
}
```

**Split:** ~50% generated, ~50% manual business logic

**Why manual additions matter:**
- Business rules unique to this domain
- Stock management is specific to gift system
- Validation messages in French for business users
- Real-world constraints (max stock 1000)

---

### Value Objects

#### Age Value Object

**File:** `src/Cadeau/Attribution/Domain/ValueObject/Age.php`

#### ✅ 100% Auto-Generated

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

    // ✅ Generated: Category helpers
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

**Generated:** 100%

**Why it's impressive:**
- Complete validation logic
- Helper methods included
- Category detection automatic
- PHP 8.1+ features (readonly, match)
- Zero manual code needed

---

#### Email Value Object

**File:** `src/Cadeau/Attribution/Domain/ValueObject/Email.php`

#### ✅ 100% Auto-Generated

```php
final readonly class Email
{
    public function __construct(
        private string $value,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        $normalized = strtolower(trim($this->value));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email format: %s', $this->value)
            );
        }

        $this->value = $normalized;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        $parts = explode('@', $this->value);
        return $parts[1] ?? '';
    }

    public function getLocalPart(): string
    {
        $parts = explode('@', $this->value);
        return $parts[0] ?? '';
    }
}
```

**Generated:** 100%

---

## 2. Application Layer

### Command Handler

**File:** `src/Cadeau/Attribution/Application/AttribuerCadeaux/AttribuerCadeauxCommandHandler.php`

#### ✅ Auto-Generated Structure

```php
final class AttribuerCadeauxCommandHandler implements MessageHandlerInterface
{
    // ✅ Generated: Constructor with dependencies
    public function __construct(
        private HabitantRepositoryInterface $habitantRepository,
        private CadeauRepositoryInterface $cadeauRepository,
        private AttributionRepositoryInterface $attributionRepository,
    ) {}

    // ✅ Generated: Handler skeleton
    public function __invoke(AttribuerCadeauxCommand $command): void
    {
        // Framework ready for business logic
    }
}
```

#### ❌ Manual Business Logic

```php
public function __invoke(AttribuerCadeauxCommand $command): void
{
    // ❌ Manual: Entity retrieval
    $habitant = $this->habitantRepository->findById($command->habitantId);
    $cadeau = $this->cadeauRepository->findById($command->cadeauId);

    // ❌ Manual: Validation
    if (!$habitant) {
        throw new \DomainException(
            "Habitant not found with ID: {$command->habitantId}"
        );
    }

    if (!$cadeau) {
        throw new \DomainException(
            "Cadeau not found with ID: {$command->cadeauId}"
        );
    }

    if (!$cadeau->isEnStock()) {
        throw new \DomainException(
            "Gift '{$cadeau->getNom()}' is out of stock"
        );
    }

    // ❌ Manual: Business logic execution
    $cadeau->diminuerStock(1);
    $attribution = Attribution::create($habitant, $cadeau);

    // ❌ Manual: Persistence
    $this->cadeauRepository->save($cadeau);
    $this->attributionRepository->save($attribution);
}
```

**Split:** ~30% generated structure, ~70% manual logic

**Why manual here:**
- Business rules vary per use case
- Validation messages are context-specific
- Orchestration logic is custom

---

### Query Handler

**File:** `src/Cadeau/Attribution/Application/RecupererHabitants/RecupererHabitantsQueryHandler.php`

#### ✅ 90% Auto-Generated

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

**Generated:** ~90% (only the specific query call might vary)

---

### Query Response

**File:** `src/Cadeau/Attribution/Application/RecupererHabitants/RecupererHabitantsResponse.php`

#### ✅ 100% Auto-Generated

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

**Generated:** 100%

**Why it's valuable:**
- DTO pattern automatically applied
- `toArray()` method for easy serialization
- Type-safe construction
- Ready for API responses

---

## 3. Infrastructure Layer

### Doctrine Repository

**File:** `src/Cadeau/Attribution/Infrastructure/Persistence/Doctrine/DoctrineHabitantRepository.php`

#### ✅ 100% Auto-Generated

```php
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

    public function delete(Habitant $habitant): void
    {
        $this->entityManager->remove($habitant);
        $this->entityManager->flush();
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Habitant::class)
            ->findAll();
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

    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }
}
```

**Generated:** 100%

**Why it's powerful:**
- Complete CRUD implementation
- Custom queries for business needs
- DQL for embedded value objects
- No boilerplate needed

---

### Doctrine Mapping

**File:** `src/Cadeau/Attribution/Infrastructure/Persistence/Doctrine/Mapping/Habitant.orm.xml`

#### ✅ 100% Auto-Generated

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Cadeau\Attribution\Domain\Model\Habitant" table="habitant">
        <id name="id" type="string" length="36">
            <generator strategy="NONE"/>
        </id>

        <field name="prenom" type="string" length="100" nullable="false"/>
        <field name="nom" type="string" length="100" nullable="false"/>

        <!-- Embedded Value Objects -->
        <embedded name="age" class="App\Cadeau\Attribution\Domain\ValueObject\Age" use-column-prefix="false">
            <field name="value" type="integer" column="age"/>
        </embedded>

        <embedded name="email" class="App\Cadeau\Attribution\Domain\ValueObject\Email" use-column-prefix="false">
            <field name="value" type="string" column="email" length="255" unique="true"/>
        </embedded>
    </entity>
</doctrine-mapping>
```

**Generated:** 100%

**Why it matters:**
- Correct XML syntax
- Embedded value objects configured
- Unique constraints applied
- Column naming handled

---

## 4. What Was Written Manually

### Controllers (100% Manual)

**File:** `src/Cadeau/Attribution/UI/Http/Web/Controller/ListHabitantsController.php`

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

**Why manual:**
- Presentation layer is application-specific
- Route configuration
- Template selection
- Response formatting

---

### Templates (100% Manual)

**File:** `templates/cadeau/attribution/list_habitants.html.twig`

```twig
{% raw %}{% extends 'base.html.twig' %}

{% block title %}Liste des Habitants{% endblock %}

{% block body %}
<div class="container mt-5">
    <h1>Liste des Habitants</h1>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Âge</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
        {% for habitant in habitants %}
            <tr>
                <td>{{ habitant.prenom }}</td>
                <td>{{ habitant.nom }}</td>
                <td>
                    {{ habitant.age.value }}
                    <span class="badge bg-{{ habitant.age.category == 'Enfant' ? 'primary' : (habitant.age.category == 'Senior' ? 'warning' : 'success') }}">
                        {{ habitant.age.category }}
                    </span>
                </td>
                <td>{{ habitant.email.value }}</td>
            </tr>
        {% endfor %}
        </tbody>
    </table>
</div>
{% endblock %}{% endraw %}
```

**Why manual:**
- Design decisions (Bootstrap)
- UX considerations
- Branding and styling

---

### Data Fixtures (100% Manual)

**File:** `src/DataFixtures/HabitantFixtures.php`

```php
class HabitantFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $habitants = [
            ['Sophie', 'Martin', 8, 'sophie.martin@example.com'],
            ['Lucas', 'Dubois', 45, 'lucas.dubois@example.com'],
            ['Marie', 'Lefebvre', 72, 'marie.lefebvre@example.com'],
            // ... more residents
        ];

        foreach ($habitants as [$prenom, $nom, $age, $email]) {
            $habitant = Habitant::create(
                prenom: $prenom,
                nom: $nom,
                age: new Age($age),
                email: new Email($email)
            );

            $manager->persist($habitant);
        }

        $manager->flush();
    }
}
```

**Why manual:**
- Demo-specific data
- Test scenarios
- Sample content

---

## 5. Code Generation Commands

Here's what was run to generate the code:

### Generate Module

```bash
php bin/console make:hexagonal:module cadeau/attribution
```

**Generated:**
- Directory structure
- Basic configuration

### Generate Entities

```bash
# Habitant with Value Objects
php bin/console make:hexagonal:entity cadeau/attribution Habitant \
    --properties="prenom:string,nom:string,age:Age,email:Email" \
    --value-objects="Age:int,Email:string,HabitantId:uuid"

# Cadeau
php bin/console make:hexagonal:entity cadeau/attribution Cadeau \
    --properties="nom:string,description:string,quantite:int"

# Attribution
php bin/console make:hexagonal:entity cadeau/attribution Attribution \
    --properties="habitant:Habitant,cadeau:Cadeau,dateAttribution:DateTimeImmutable"
```

**Generated:**
- Entity classes with factory methods
- Value Object classes
- Repository interfaces
- Doctrine repositories
- Doctrine mappings (XML)

### Generate Use Cases

```bash
# Command
php bin/console make:hexagonal:use-case cadeau/attribution AttribuerCadeaux command

# Queries
php bin/console make:hexagonal:use-case cadeau/attribution RecupererHabitants query
php bin/console make:hexagonal:use-case cadeau/attribution RecupererCadeaux query
php bin/console make:hexagonal:use-case cadeau/attribution RecupererStatistiques query
```

**Generated:**
- Command/Query classes
- Handler classes
- Response DTOs

---

## 6. Time Savings

### Without hexagonal-maker-bundle

Estimated time to write all core code manually:

| Task | Time |
|------|------|
| Create directory structure | 15 min |
| Write 3 entities + validation | 60 min |
| Write 3 value objects | 45 min |
| Write 3 repository interfaces | 30 min |
| Write 3 Doctrine adapters | 60 min |
| Write Doctrine mappings (XML) | 45 min |
| Write CQRS handlers (4) | 90 min |
| Write response DTOs | 30 min |
| **Total** | **6 hours** |

### With hexagonal-maker-bundle

| Task | Time |
|------|------|
| Run generation commands | 10 min |
| Add business logic to Cadeau | 30 min |
| Complete command handler logic | 20 min |
| **Total** | **1 hour** |

**Time Saved: 5 hours (83%)**

---

## 7. Code Quality Comparison

### Generated Code Benefits

1. **Consistency** - Same patterns everywhere
2. **Best Practices** - PHP 8.1+ features, readonly, type hints
3. **Complete** - Nothing forgotten (factory methods, validation, etc.)
4. **Tested** - Templates are tested and proven
5. **Maintained** - Updates to bundle improve all projects

### Manual Code Benefits

1. **Business-Specific** - Exact requirements met
2. **Context-Aware** - Error messages in French for users
3. **Optimized** - Only what's needed
4. **Flexible** - Can deviate from patterns when needed

---

## 8. Key Insights

### What Generates Well

- **Entity structures** - Boilerplate, getters, validation framework
- **Value Objects** - Complete implementations possible
- **Repository adapters** - Infrastructure code is repetitive
- **CQRS scaffolding** - Structure and DI
- **Doctrine mappings** - Technical configuration

### What Needs Manual Work

- **Complex business rules** - Domain-specific logic (stock limits, etc.)
- **Validation messages** - User-facing text
- **Use case orchestration** - Command handler business flow
- **Presentation layer** - UI, templates, routes
- **Test data** - Fixtures and samples

---

## 9. Best Practices Learned

### When Using the Bundle

1. **Generate First** - Always start with code generation
2. **Then Customize** - Add business logic after
3. **Don't Fight It** - Follow the patterns
4. **Keep Generated Code** - Don't delete framework
5. **Extend, Don't Replace** - Add methods, keep structure

### Project Organization

1. **One Module Per Context** - Clear boundaries
2. **Value Objects for Primitives** - Validation everywhere
3. **Factory Methods Always** - Control entity creation
4. **CQRS for All Operations** - Consistency
5. **Pure Domain** - Zero framework dependencies

---

## 10. Conclusion

### The Power of Code Generation

- **850 lines** of core code auto-generated
- **200 lines** of business logic added manually
- **81% generation rate** for hexagonal architecture
- **5 hours saved** on initial development
- **Zero boilerplate bugs** - tested templates

### The Value of Manual Code

- **Unique business rules** implemented correctly
- **User-facing content** in appropriate language
- **Optimized flows** for specific use cases
- **Custom presentation** layer

{: .highlight }
**The Sweet Spot:** Use code generation for architecture and structure, write manual code for business logic and presentation. This demo achieves 95% functionality with 81% generated code - proving the bundle's value while showing where developers add unique value.

---

## Try It Yourself

Want to explore the code?

1. **Clone the repo:**
   ```bash
   git clone https://github.com/ahmed-bhs/hexagonal-demo
   ```

2. **Compare files:**
   - Look at `Cadeau.php` - see generated vs manual
   - Check `DoctrineHabitantRepository.php` - 100% generated
   - Review `AttribuerCadeauxCommandHandler.php` - mixed

3. **Generate your own:**
   ```bash
   composer require ahmed-bhs/hexagonal-maker-bundle
   php bin/console make:hexagonal:module my-module
   ```

---

{: .fs-3 }
**Ready to build your own hexagonal application?**
Try the [hexagonal-maker-bundle](https://github.com/ahmed-bhs/hexagonal-maker-bundle) today!
