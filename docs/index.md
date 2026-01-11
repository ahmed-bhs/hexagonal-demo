---
layout: default
title: Home
nav_order: 1
description: "Hexagonal Demo - A complete Symfony application demonstrating hexagonal architecture, CQRS, and DDD patterns with 95% auto-generated code."
permalink: /
---

# Hexagonal Demo
{: .fs-9 }

A complete Symfony application demonstrating hexagonal architecture in action with 95% auto-generated code.
{: .fs-6 .fw-300 }

[Get Started](quick-start){: .btn .btn-primary .fs-5 .mb-4 .mb-md-0 .mr-2 }
[View on GitHub](https://github.com/ahmed-bhs/hexagonal-demo){: .btn .fs-5 .mb-4 .mb-md-0 }

---

## What is This?

**Hexagonal Demo** is a fully functional gift management system built with Symfony that showcases:

- **Hexagonal Architecture** (Ports & Adapters)
- **Domain-Driven Design** (DDD) patterns
- **CQRS** (Command Query Responsibility Segregation)
- **95% Code Auto-Generation** using [hexagonal-maker-bundle](https://github.com/ahmed-bhs/hexagonal-maker-bundle)

{: .note }
This is a live, working application - not just code snippets. You can clone it, run it, and see hexagonal architecture in action within minutes.

---

## Why This Demo?

Most architecture tutorials show you the theory. This demo shows you a **complete, production-ready application** that:

- ✅ Actually runs and works
- ✅ Follows best practices
- ✅ Includes database integration
- ✅ Has a web interface
- ✅ Demonstrates real use cases
- ✅ Shows the power of code generation

---

## What You'll Find

### Three Domain Modules

1. **Habitants (Residents)** - Manage residents with age and email validation
2. **Cadeaux (Gifts)** - Gift catalog with stock management
3. **Attribution** - Gift assignment to residents

### Complete Hexagonal Structure

```
src/Cadeau/Attribution/
│
├── Domain/              💎 Pure business logic (no framework)
│   ├── Model/          Entities: Habitant, Cadeau, Attribution
│   ├── ValueObject/    Age, Email, HabitantId with validation
│   └── Port/           Repository interfaces
│
├── Application/         ⚙️  Use cases (CQRS)
│   ├── Commands/       AttribuerCadeaux
│   └── Queries/        RecupererHabitants, RecupererCadeaux
│
├── Infrastructure/      🔌 Adapters
│   └── Doctrine/       Repository implementations
│
└── UI/                  🎮 Controllers
    └── Web/            Web controllers
```

---

## Key Features

### Auto-Generated Code

- **Entities** with factory methods and business logic
- **Value Objects** with validation (Age, Email, UUID)
- **Repository interfaces** with common methods
- **Doctrine adapters** with optimized queries
- **Command/Query handlers** with dependency injection
- **Doctrine mappings** (XML) for persistence

### Manual Additions

- Web controllers for presentation
- Twig templates for UI
- Business-specific logic (stock management)
- Data fixtures for demo

---

## Quick Stats

| Category | Auto-Generated | Manual | % Auto |
|----------|---------------|--------|--------|
| **Domain Layer** | ~400 lines | ~150 lines | 73% |
| **Application Layer** | ~200 lines | ~50 lines | 80% |
| **Infrastructure Layer** | ~250 lines | 0 lines | 100% |
| **UI Layer** | 0 lines | ~350 lines | 0% |

**Total Core (excluding UI):** 85% auto-generated

{: .highlight }
Without the hexagonal-maker-bundle, creating this architecture would take 3-4 hours. With the bundle, it takes about 1 hour (mainly for business logic).

---

## Live Demo Features

### Web Interface

- **Dashboard** (`/`) - Statistics and overview
- **Residents List** (`/habitants`) - All residents with categories
- **Gifts Catalog** (`/cadeaux`) - Available gifts with stock status

### Database

- 10 sample residents (children, adults, seniors)
- 10 different gifts
- 7 pre-configured attributions

### CQRS in Action

- Commands for writes (AttribuerCadeaux)
- Queries for reads (RecupererHabitants, RecupererCadeaux)
- Symfony Messenger as message bus

---

## Technology Stack

- **PHP 8.1+** - Modern PHP features
- **Symfony 6.4+** - Web framework
- **Doctrine ORM** - Database abstraction
- **Symfony Messenger** - CQRS implementation
- **Bootstrap 5** - UI components
- **hexagonal-maker-bundle** - Code generator

---

## Who Is This For?

This demo is perfect for:

- **Developers** learning hexagonal architecture
- **Architects** evaluating architectural patterns
- **Teachers** showing DDD and CQRS in practice
- **Teams** starting new Symfony projects
- **Students** understanding clean architecture
- **Interviewers** assessing architecture knowledge

---

## What You'll Learn

### Architecture Patterns

1. **Hexagonal Architecture** - Ports & Adapters pattern
2. **Domain-Driven Design** - Entities, Value Objects, Aggregates
3. **CQRS** - Separating reads from writes
4. **Dependency Inversion** - Domain defines interfaces
5. **Repository Pattern** - Abstracting persistence

### Symfony Skills

- Command/Query handlers with Messenger
- Doctrine custom types for Value Objects
- Service configuration and DI
- Doctrine mappings (XML)
- Controller best practices

### Code Generation

- How to scaffold hexagonal modules
- Extending generated code
- When to generate vs when to write manually
- Maintaining separation of concerns

---

## Next Steps

<div class="code-example" markdown="1">

**Ready to dive in?**

1. [Quick Start Guide](quick-start) - Get the app running in 5 minutes
2. [Architecture Overview](architecture) - Understand the structure
3. [Features Guide](features) - Explore what's implemented
4. [Code Tour](code-tour) - See generated vs manual code
5. [API Documentation](api) - Learn the endpoints

</div>

---

## Open Source

This demo is open source under the MIT license.

**Repository:** [ahmed-bhs/hexagonal-demo](https://github.com/ahmed-bhs/hexagonal-demo)
**Bundle:** [ahmed-bhs/hexagonal-maker-bundle](https://github.com/ahmed-bhs/hexagonal-maker-bundle)

---

{: .fs-3 }
**Created by [Ahmed EBEN HASSINE](https://github.com/ahmed-bhs)**
*Demonstrating the power of hexagonal architecture with Symfony*
