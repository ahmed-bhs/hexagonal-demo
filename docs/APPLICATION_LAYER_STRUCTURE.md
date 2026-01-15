# Application Layer Structure

This document explains the complete structure of the Application layer in our hexagonal architecture.

## 📁 Complete Structure

```
Application/
├── Command/              # Write operations (CQRS)
├── Query/                # Read operations (CQRS)
├── Service/              # Application Services (orchestration)
├── DTO/                  # Data Transfer Objects
├── Exception/            # Application-level exceptions
├── EventHandler/         # Domain event handlers (optional)
├── Specification/        # Business rules (optional)
└── Policy/               # Saga pattern (optional)
```

---

## 🎯 1. Command/ - Write Operations

**Purpose:** Modify system state (create, update, delete)

**Contains:**
- Command classes (DTOs for write operations)
- CommandHandler classes (use case implementation)
- CommandValidator classes (business validation)

**Example:**
```php
Command/
└── AttribuerCadeau/
    ├── AttribuerCadeauCommand.php          # DTO with write data
    └── AttribuerCadeauCommandHandler.php   # Execute use case
```

**When to use:**
- ✅ Every write operation
- ✅ CQRS pattern

**When NOT to use:**
- ❌ Read operations (use Query/)

---

## 🎯 2. Query/ - Read Operations

**Purpose:** Read data without modifying state

**Contains:**
- Query classes (DTOs for read criteria)
- QueryHandler classes (read implementation)
- Response classes (DTOs for read results)

**Example:**
```php
Query/
└── RecupererHabitants/
    ├── RecupererHabitantsQuery.php         # Search criteria
    ├── RecupererHabitantsQueryHandler.php  # Fetch data
    └── RecupererHabitantsResponse.php      # Result DTO
```

**When to use:**
- ✅ Every read operation
- ✅ CQRS pattern

---

## 🎯 3. Service/ - Application Services

**Purpose:** Orchestrate complex workflows across multiple use cases

**Contains:**
- Application service classes that coordinate Commands/Queries

**Example:**
```php
Service/
└── AutomaticGiftAttributionService.php
```

**Key characteristics:**
- Orchestrates multiple Commands/Queries
- NO business logic (delegates to Domain)
- Manages transaction boundaries
- Coordinates multiple aggregates

**When to use:**
- ✅ Complex workflow across multiple use cases
- ✅ Need to query before executing command
- ✅ Batch operations
- ✅ Saga pattern coordination

**When NOT to use:**
- ❌ Simple CRUD (use Command/Query directly)
- ❌ Business rules (belongs in Domain)
- ❌ Single use case (use CommandHandler)

**Real-world examples:**
- `AutomaticGiftAttributionService`: Query available gifts + select + attribute
- `BulkImportService`: Read CSV + validate + create multiple entities
- `OrderCheckoutService`: Validate cart + reserve stock + create order + process payment

---

## 🎯 4. DTO/ - Data Transfer Objects

**Purpose:** Transfer data between layers (Application ↔ UI)

**Contains:**
- Read-only data containers
- Serialization methods (toArray, toJson)
- Factory methods (fromEntity, fromArray)

**Example:**
```php
DTO/
├── AttributionResultDTO.php    # Service result
├── GiftDTO.php                 # Gift data transfer
├── HabitantDTO.php             # Resident data transfer
└── GiftRequestSummaryDTO.php   # Complex aggregated data
```

**Key characteristics:**
- Immutable (readonly)
- No business logic
- Optimized for serialization
- Can have presentation logic (formatting, computed fields)

**DTO vs Domain Entity:**
| Aspect | DTO | Domain Entity |
|--------|-----|---------------|
| Purpose | Data transfer | Business logic |
| Behavior | None | Rich behavior |
| Validation | Format only | Business rules |
| Mutability | Immutable | Can be mutable |
| Layer | Application/UI | Domain |

**When to use:**
- ✅ API responses (REST, GraphQL)
- ✅ Query results with computed fields
- ✅ Aggregating data from multiple sources
- ✅ Decoupling UI from Domain structure

**When NOT to use:**
- ❌ Within Domain layer
- ❌ Simple CRUD (use entities directly)
- ❌ Between methods in same class

---

## 🎯 5. Exception/ - Application Exceptions

**Purpose:** Handle use case failures and workflow errors

**Contains:**
- Application-level exception classes
- Structured error information
- Context for debugging

**Example:**
```php
Exception/
├── NoEligibleGiftException.php           # Use case failure
├── GiftAttributionFailedException.php    # Workflow error
└── InvalidDemandeCadeauException.php     # Validation error
```

**Application vs Domain Exceptions:**

| Type | Application Exception | Domain Exception |
|------|----------------------|------------------|
| **What** | Use case failures | Invariant violations |
| **When** | Workflow errors | Business rule violations |
| **Where** | CommandHandler, Service | Entity, Value Object |
| **Example** | NoEligibleGiftException | InvalidEmailException |

**When to use:**
- ✅ Use case cannot be completed
- ✅ External service unavailable
- ✅ Quota exceeded
- ✅ Resource not found

**When NOT to use:**
- ❌ Domain invariant violations (use Domain exceptions)
- ❌ Infrastructure errors (use Infrastructure exceptions)

---

## 🎯 6. EventHandler/ - Domain Event Handlers (Optional)

**Purpose:** React to domain events with business policies

**Contains:**
- Event handler classes that implement business policies

**Example:**
```php
EventHandler/
└── OnGiftAttributed/
    └── NotifyAdminIfVIPHandler.php
```

**EventHandler vs Infrastructure EventSubscriber:**

| Aspect | EventHandler (Application) | EventSubscriber (Infrastructure) |
|--------|---------------------------|----------------------------------|
| **Concern** | Business policy | Technical concern |
| **Example** | Update quota, notify admin | Send email, save to EventStore |
| **Dependencies** | Domain ports | Symfony Mailer, Doctrine |

**When to use:**
- ✅ Business policies (sagas)
- ✅ Cross-aggregate coordination
- ✅ Business workflows triggered by events

**When NOT to use:**
- ❌ Technical concerns (use Infrastructure)
- ❌ Simple reactions (use Infrastructure EventSubscriber)

---

## 🎯 7. Specification/ - Business Rules (Optional)

**Purpose:** Encapsulate reusable business rules

**Contains:**
- Specification classes implementing business rules

**Example:**
```php
Specification/
├── IsEligibleForGiftSpecification.php
└── HasStockAvailableSpecification.php
```

**When to use:**
- ✅ Complex business rules
- ✅ Reused across multiple use cases
- ✅ Combinable rules (AND, OR, NOT)

**When NOT to use:**
- ❌ Simple validation (use CommandValidator)
- ❌ One-time rules (inline in handler)

---

## 🎯 8. Policy/ - Saga Pattern (Optional)

**Purpose:** Coordinate long-running transactions across aggregates

**Contains:**
- Policy classes that orchestrate multiple commands

**Example:**
```php
Policy/
└── GiftAttributionPolicy.php
```

**When to use:**
- ✅ Multi-step workflows
- ✅ Distributed transactions
- ✅ Compensation logic

**When NOT to use:**
- ❌ Simple workflows (use Service)
- ❌ Single aggregate operations

---

## 📊 Decision Matrix: When to Use What?

| Scenario | Use |
|----------|-----|
| Simple write operation | Command + CommandHandler |
| Simple read operation | Query + QueryHandler |
| Complex workflow (query + command) | Service |
| API response data | DTO |
| Use case failure | Application Exception |
| Reusable business rule | Specification |
| React to domain event (business) | EventHandler |
| Multi-step transaction | Policy |

---

## 🏗️ Recommended Structure Levels

### 🟢 Minimal (small project)
```
Application/
├── Command/
└── Query/
```

### 🟡 Medium (growing project)
```
Application/
├── Command/
├── Query/
├── DTO/              # When UI needs specific formats
└── Exception/        # When you have business errors
```

### 🔴 Complete (large/complex project)
```
Application/
├── Command/
├── Query/
├── Service/          # Complex orchestration
├── DTO/
├── Exception/
├── EventHandler/     # Business policies
├── Specification/    # Reusable rules
└── Policy/           # Sagas
```

---

## ✅ Best Practices

1. **Start minimal** - Add folders only when needed (YAGNI)
2. **Command/Query always** - Core CQRS pattern
3. **DTOs for APIs** - Decouple UI from Domain
4. **Services sparingly** - Most use cases fit in CommandHandler
5. **Exceptions for workflow** - Not for validation (use validators)
6. **EventHandlers for policies** - Business reactions to events
7. **Keep thin** - Delegate to Domain for business logic

---

## 📚 Real Examples in This Project

### Example 1: Simple Command
```
Application/Command/AttribuerCadeau/
├── AttribuerCadeauCommand.php
└── AttribuerCadeauCommandHandler.php
```

### Example 2: Complex Service
```
Application/Service/AutomaticGiftAttributionService.php
  → Uses Query/RecupererCadeaux
  → Uses Command/AttribuerCadeau
  → Returns DTO/AttributionResultDTO
  → Throws Exception/NoEligibleGiftException
```

### Example 3: DTO for API
```php
// QueryHandler returns DTO
$gifts = array_map(
    fn(Cadeau $cadeau) => GiftDTO::fromEntity($cadeau),
    $cadeaux
);

// Controller returns JSON
return new JsonResponse($gifts);
```

---

## 🎯 Key Takeaway

> **Application Layer = Use Case Orchestration**
>
> - Coordinates Domain operations
> - NO business logic (delegates to Domain)
> - Thin and focused
> - Use cases should be obvious from folder structure
