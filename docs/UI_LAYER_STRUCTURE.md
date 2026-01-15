# UI Layer Structure

This document explains what belongs in the UI layer of our hexagonal architecture.

## 📁 Complete Structure

```
UI/
├── Http/                  # HTTP interface
│   ├── Controller/        # HTTP controllers
│   ├── Form/              # Form types (Symfony)
│   ├── Request/           # Request DTOs (validation)
│   ├── Presenter/         # Response formatting
│   └── Middleware/        # HTTP-specific middleware
├── Cli/                   # Command-line interface
│   └── Command/           # Console commands
├── GraphQL/               # GraphQL interface (optional)
│   ├── Resolver/          # GraphQL resolvers
│   └── Type/              # GraphQL types
├── Api/                   # REST API (optional)
│   ├── Controller/        # API controllers
│   └── Serializer/        # Custom serializers
└── WebSocket/             # Real-time interface (optional)
    └── Handler/           # WebSocket handlers
```

---

## 🎯 1. Http/Controller/ - HTTP Controllers

**Purpose:** Handle HTTP requests and return HTTP responses

**Responsibilities:**
- Parse HTTP requests
- Validate input format (not business rules)
- Dispatch commands/queries via buses
- Format responses (JSON, HTML)
- Handle HTTP-specific concerns (status codes, headers)

**Example:**
```php
Http/Controller/
├── ListHabitantsController.php
├── ListCadeauxController.php
├── AutomaticAttributionController.php
└── DemandeCadeauFormController.php
```

**Key characteristics:**
- Thin controllers (no business logic)
- Depend on Application layer (Commands/Queries/Services)
- Return HTTP responses (JsonResponse, Response)
- Handle HTTP exceptions

**What controllers SHOULD do:**
- ✅ Parse request (JSON, form data, query params)
- ✅ Dispatch to Command/Query bus
- ✅ Format response (DTO → JSON/HTML)
- ✅ Handle HTTP status codes
- ✅ Map exceptions to HTTP errors

**What controllers SHOULD NOT do:**
- ❌ Business logic (belongs in Domain)
- ❌ Orchestration (belongs in Application Service)
- ❌ Database queries (use QueryHandler)
- ❌ Direct entity manipulation

---

## 🎯 2. Http/Form/ - Symfony Form Types

**Purpose:** Define and validate HTML forms

**Responsibilities:**
- Define form structure
- Map form fields to DTOs/Commands
- Apply format validation (constraints)
- Render form views

**Example:**
```php
Http/Form/
├── DemandeCadeauType.php
├── HabitantSearchType.php
└── GiftFilterType.php
```

**Example implementation:**
```php
namespace App\Cadeau\Demande\UI\Http\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class DemandeCadeauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requesterName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('requesterEmail', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('requestedGift', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]);
    }
}
```

**When to use:**
- ✅ HTML forms for web UI
- ✅ Need visual form rendering
- ✅ Complex validation (dependent fields)

**When NOT to use:**
- ❌ REST API (use Request DTOs instead)
- ❌ GraphQL (use input types)
- ❌ Simple CRUD (forms might be overkill)

---

## 🎯 3. Http/Request/ - Request DTOs

**Purpose:** Validate and structure incoming HTTP requests

**Responsibilities:**
- Parse and validate request data
- Type-safe request handling
- Separate validation from controller logic
- Convert to Commands/Queries

**Example:**
```php
Http/Request/
├── CreateGiftRequestDTO.php
├── AttributeGiftRequestDTO.php
└── SearchHabitantsRequestDTO.php
```

**Example implementation:**
```php
namespace App\Cadeau\Attribution\UI\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AttributeGiftRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $habitantId,

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $giftId,

        #[Assert\Length(max: 500)]
        public ?string $notes = null,
    ) {}

    /**
     * Create from HTTP request array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            habitantId: $data['habitantId'] ?? '',
            giftId: $data['giftId'] ?? '',
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Convert to Application Command
     */
    public function toCommand(): AttribuerCadeauCommand
    {
        return new AttribuerCadeauCommand(
            habitantId: $this->habitantId,
            cadeauId: $this->giftId,
            notes: $this->notes,
        );
    }
}
```

**Usage in controller:**
```php
#[Route('/api/attributions', methods: ['POST'])]
public function create(Request $request): JsonResponse
{
    // Parse request
    $requestDTO = AttributeGiftRequestDTO::fromArray(
        $request->toArray()
    );

    // Validate
    $errors = $this->validator->validate($requestDTO);
    if (count($errors) > 0) {
        return new JsonResponse(['errors' => $errors], 400);
    }

    // Convert to Command and dispatch
    $command = $requestDTO->toCommand();
    $this->commandBus->dispatch($command);

    return new JsonResponse(['success' => true], 201);
}
```

**Request DTO vs Form vs Command:**

| Aspect | Request DTO | Form | Command |
|--------|------------|------|---------|
| **Layer** | UI | UI | Application |
| **Purpose** | Validate HTTP input | Render/validate HTML forms | Business operation |
| **Validation** | Format (UUID, email, length) | Format + UX | Business rules |
| **Dependencies** | Symfony Validator | Symfony Form | None (pure PHP) |
| **Use case** | REST API | HTML forms | Any use case |

**When to use:**
- ✅ REST API endpoints
- ✅ Type-safe request handling
- ✅ Separate validation from controller
- ✅ Complex request structures

---

## 🎯 4. Http/Presenter/ - Response Formatters

**Purpose:** Format Application DTOs for specific UI needs

**Responsibilities:**
- Transform DTOs for specific views
- Add UI-specific formatting
- Handle localization
- Compute display values

**Example:**
```php
Http/Presenter/
├── GiftPresenter.php
├── HabitantPresenter.php
└── AttributionSummaryPresenter.php
```

**Example implementation:**
```php
namespace App\Cadeau\Attribution\UI\Http\Presenter;

use App\Cadeau\Attribution\Application\DTO\GiftDTO;

final readonly class GiftPresenter
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * Format for HTML display
     */
    public function toHtml(GiftDTO $gift): array
    {
        return [
            'id' => $gift->id,
            'name' => $gift->name,
            'description' => $gift->description,
            'stockStatus' => $this->formatStockStatus($gift),
            'stockStatusClass' => $this->getStockStatusClass($gift),
            'category' => $this->translator->trans(
                "gift.category.{$gift->category}"
            ),
            'isAvailable' => $gift->quantity > 0,
        ];
    }

    /**
     * Format for JSON API
     */
    public function toApi(GiftDTO $gift): array
    {
        return [
            'id' => $gift->id,
            'name' => $gift->name,
            'description' => $gift->description,
            'category' => $gift->category,
            'quantity' => $gift->quantity,
            'stockLevel' => $this->getStockLevel($gift),
            'available' => $gift->quantity > 0,
        ];
    }

    private function formatStockStatus(GiftDTO $gift): string
    {
        return match (true) {
            $gift->quantity === 0 => $this->translator->trans('gift.out_of_stock'),
            $gift->quantity < 5 => $this->translator->trans(
                'gift.low_stock',
                ['count' => $gift->quantity]
            ),
            default => $this->translator->trans('gift.in_stock'),
        };
    }

    private function getStockStatusClass(GiftDTO $gift): string
    {
        return match (true) {
            $gift->quantity === 0 => 'text-danger',
            $gift->quantity < 5 => 'text-warning',
            default => 'text-success',
        };
    }

    private function getStockLevel(GiftDTO $gift): string
    {
        return match (true) {
            $gift->quantity === 0 => 'out_of_stock',
            $gift->quantity < 5 => 'low',
            $gift->quantity < 20 => 'medium',
            default => 'high',
        };
    }
}
```

**Presenter vs DTO:**

| Aspect | Presenter (UI) | DTO (Application) |
|--------|---------------|-------------------|
| **Purpose** | UI-specific formatting | Data transfer |
| **Layer** | UI | Application |
| **Logic** | Display logic (CSS classes, i18n) | Minimal (computed fields) |
| **Dependencies** | Translator, Router, etc. | None |
| **Reusability** | One UI type | All UIs |

**When to use:**
- ✅ Different formats for same data (HTML vs API)
- ✅ UI-specific computed values (CSS classes)
- ✅ Localization/translation
- ✅ Complex display logic

**When NOT to use:**
- ❌ Simple pass-through (use DTO directly)
- ❌ Business logic (belongs in Domain)

---

## 🎯 5. Http/Middleware/ - HTTP Middleware

**Purpose:** Handle cross-cutting HTTP concerns

**Responsibilities:**
- Authentication/authorization
- Request/response transformation
- Logging
- Rate limiting
- CORS headers

**Example:**
```php
Http/Middleware/
├── AuthenticationMiddleware.php
├── RateLimitMiddleware.php
└── CorsMiddleware.php
```

**Note:** In Symfony, this is often handled by:
- Event Listeners (kernel.request, kernel.response)
- Security system
- Built-in features

---

## 🎯 6. Cli/Command/ - Console Commands

**Purpose:** Provide command-line interface

**Responsibilities:**
- Parse CLI arguments
- Dispatch commands/queries
- Display output
- Handle CLI-specific concerns

**Example:**
```php
Cli/Command/
├── ImportGiftsCommand.php
├── AttributeGiftsCommand.php
└── GenerateReportCommand.php
```

**Example implementation:**
```php
namespace App\Cadeau\Attribution\UI\Cli\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:attribute-gifts',
    description: 'Automatically attribute gifts to all eligible residents',
)]
final class AttributeGiftsCommand extends Command
{
    public function __construct(
        private readonly AutomaticGiftAttributionService $attributionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting automatic gift attribution...');

        try {
            // Use Application Service
            $results = $this->attributionService->attributeAllEligible();

            $output->writeln(sprintf(
                'Attributed %d gifts successfully',
                count($results)
            ));

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $output->writeln('<error>Failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
```

**When to use:**
- ✅ Background jobs
- ✅ Batch operations
- ✅ Admin tasks
- ✅ Data import/export

---

## 🎯 7. GraphQL/ (Optional)

**Purpose:** Provide GraphQL API interface

```php
GraphQL/
├── Resolver/
│   ├── GiftResolver.php
│   └── HabitantResolver.php
└── Type/
    ├── GiftType.php
    └── HabitantType.php
```

**When to use:**
- ✅ Need flexible queries
- ✅ Mobile/SPA clients
- ✅ Avoid over-fetching

---

## 🎯 8. Api/ (Optional)

**Purpose:** REST API specific controllers and serializers

```php
Api/
├── Controller/
│   ├── GiftController.php
│   └── HabitantController.php
└── Serializer/
    └── GiftNormalizer.php
```

**When to use:**
- ✅ Dedicated REST API
- ✅ Different from web controllers
- ✅ API versioning

---

## 📊 What Belongs in UI Layer?

| Component | Belongs in UI? | Reason |
|-----------|----------------|--------|
| **Controllers** | ✅ YES | HTTP request handling |
| **Forms** | ✅ YES | HTML form handling |
| **Request DTOs** | ✅ YES | HTTP request validation |
| **Presenters** | ✅ YES | UI-specific formatting |
| **Templates** | ✅ YES | View rendering |
| **Console Commands** | ✅ YES | CLI interface |
| **Event Listeners (HTTP)** | ✅ YES | HTTP-specific (CORS, auth) |
| **Business Logic** | ❌ NO | Belongs in Domain |
| **Orchestration** | ❌ NO | Belongs in Application |
| **Database Queries** | ❌ NO | Belongs in Infrastructure |
| **Event Subscribers (Domain)** | ❌ NO | Belongs in Infrastructure |

---

## ✅ Key Principles

1. **Thin UI layer** - No business logic, only presentation logic
2. **Depend on Application** - Use Commands/Queries/Services
3. **Never skip Application** - UI → Application → Domain (never UI → Domain)
4. **Multiple UIs possible** - Http, Cli, GraphQL, WebSocket all use same Application layer
5. **UI-specific concerns only** - HTTP status codes, form rendering, CLI formatting

---

## 🏗️ Architecture Flow

```
┌──────────────┐
│   Browser    │ HTTP Request (JSON/Form)
└───────┬──────┘
        ↓
┌──────────────────────────────────────┐
│              UI Layer                │
│  ┌────────────────────────────────┐  │
│  │ Controller                     │  │
│  │ - Parse request                │  │
│  │ - Validate format (RequestDTO) │  │
│  └────────────┬───────────────────┘  │
│               ↓                       │
│  ┌────────────────────────────────┐  │
│  │ Dispatch Command/Query         │  │
│  └────────────┬───────────────────┘  │
└───────────────┼───────────────────────┘
                ↓
┌───────────────────────────────────────┐
│        Application Layer              │
│  CommandHandler / QueryHandler        │
│  ↓                                    │
│  Business logic in Domain             │
└───────────┬───────────────────────────┘
            ↓
┌───────────────────────────────────────┐
│              UI Layer                 │
│  ┌────────────────────────────────┐   │
│  │ Format response                │   │
│  │ - DTO → Presenter → JSON/HTML  │   │
│  └────────────────────────────────┘   │
└───────────┬───────────────────────────┘
            ↓
    ┌──────────────┐
    │   Browser    │ HTTP Response
    └──────────────┘
```

---

## 📚 Examples in This Project

### Example 1: Simple Controller
```php
// UI/Http/Controller/ListHabitantsController.php
public function __invoke(Request $request): Response
{
    // Dispatch Query
    $habitants = $this->queryBus->dispatch(
        new RecupererHabitantsQuery()
    );

    // Return response
    return $this->json($habitants);
}
```

### Example 2: Form + Controller
```php
// UI/Http/Controller/DemandeCadeauFormController.php
public function submit(Request $request): Response
{
    $form = $this->createForm(DemandeCadeauType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Dispatch Command
        $this->commandBus->dispatch(
            new SoumettreDemandeCadeauCommand(...)
        );
    }

    return $this->render('demande/form.html.twig', [
        'form' => $form,
    ]);
}
```

### Example 3: REST API with Request DTO
```php
// UI/Http/Controller/AutomaticAttributionController.php
public function automatic(Request $request): JsonResponse
{
    // Parse and validate
    $requestDTO = AttributeGiftRequestDTO::fromArray(
        json_decode($request->getContent(), true)
    );

    // Dispatch to Service
    $result = $this->attributionService->attributeBestGift(
        $requestDTO->habitantId
    );

    // Format response
    return new JsonResponse($result->toArray());
}
```

---

## 🎯 Summary

> **UI Layer = Interface Adapters**
>
> - Handles protocol-specific concerns (HTTP, CLI, GraphQL)
> - Thin layer (no business logic)
> - Depends on Application layer
> - Multiple UIs can share same Application layer
> - Controllers, Forms, Request DTOs, Presenters, Console Commands
