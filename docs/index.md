# 🎁 Hexagonal Demo

<p align="center">
  <strong>Live Demo of Hexagonal Architecture with Symfony</strong>
</p>

<p align="center">
  <a href="https://github.com/ahmed-bhs/hexagonal-demo"><img src="https://img.shields.io/badge/GitHub-Repository-181717?logo=github" alt="GitHub"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php" alt="PHP 8.1+"></a>
  <a href="https://symfony.com"><img src="https://img.shields.io/badge/Symfony-6.4%2B-000000?logo=symfony" alt="Symfony"></a>
  <a href="https://github.com/ahmed-bhs/hexagonal-demo/blob/main/LICENSE"><img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License"></a>
</p>

<p align="center">
  🎯 <strong>95% Auto-Generated</strong> | 💎 <strong>Pure Domain</strong> | 🎮 <strong>Working Demo</strong> | 📚 <strong>Educational</strong>
</p>

---

## What is This?

A **complete, working Symfony application** demonstrating hexagonal architecture in action:

- **Gift Management System** - Residents, Gifts, and Attributions
- **95% Code Auto-Generated** with [hexagonal-maker-bundle](https://github.com/ahmed-bhs/hexagonal-maker-bundle)
- **Pure Hexagonal Architecture** - Domain, Application, Infrastructure, UI layers
- **CQRS Pattern** - Commands and Queries properly separated
- **DDD Patterns** - Entities, Value Objects, Aggregates, Repositories

---

## ⚡ Quick Demo

### What You'll See

=== "Dashboard"

    **Home Page**: Statistics and overview

    - Total residents count
    - Total gifts available
    - Attributions made
    - Age distribution chart

=== "Residents"

    **Residents List**: `/habitants`

    - All residents with details
    - Categories: Child / Adult / Senior
    - Email validation
    - Age with category badges

=== "Gifts"

    **Gifts Catalog**: `/cadeaux`

    - Available gifts
    - Stock status (available/out of stock)
    - Gift descriptions
    - Bootstrap design

---

## 🏗️ Architecture

### Hexagonal Structure

```
src/Cadeau/Attribution/
│
├── Domain/                    💎 PURE BUSINESS LOGIC
│   ├── Model/
│   │   ├── Habitant.php       ← Resident entity
│   │   ├── Cadeau.php         ← Gift entity
│   │   └── Attribution.php    ← Attribution aggregate
│   ├── ValueObject/
│   │   ├── HabitantId.php     ← UUID
│   │   ├── Age.php            ← Age with validation
│   │   └── Email.php          ← Email with validation
│   └── Port/
│       └── *RepositoryInterface.php  ← Interfaces
│
├── Application/               ⚙️ USE CASES (CQRS)
│   ├── AttribuerCadeaux/      ← Command
│   └── RecupererHabitants/    ← Query
│
├── Infrastructure/            🔌 ADAPTERS
│   └── Persistence/Doctrine/
│       └── Doctrine*Repository.php
│
└── UI/                        🎮 PRIMARY ADAPTERS
    └── Http/Web/Controller/
```

[See detailed architecture →](architecture/overview.md)

---

## 🚀 Run Locally

### Quick Start

```bash
# Clone
git clone https://github.com/ahmed-bhs/hexagonal-demo
cd hexagonal-demo

# Install
composer install

# Database
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load

# Run
symfony server:start
```

**Access:** http://localhost:8000

[Full installation guide →](getting-started/installation.md)

---

## 📊 Code Statistics

| Category | Lines Generated | Lines Manual | % Auto |
|----------|----------------|--------------|--------|
| **Domain** | ~400 | ~150 | 73% |
| **Application** | ~200 | ~50 | 80% |
| **Infrastructure** | ~250 | 0 | 100% |
| **UI** | 0 | ~350 | 0% |
| **Total Core** | **~850** | **~550** | **61%** |

**Excluding UI/Fixtures:** **85% auto-generated** ✨

[See detailed statistics →](about/statistics.md)

---

## 🎓 What You'll Learn

### Hexagonal Architecture Concepts

1. **Pure Domain** - Zero framework dependencies
2. **Dependency Inversion** - Domain defines interfaces
3. **CQRS** - Command/Query separation
4. **Value Objects** - Encapsulated validation
5. **Ports & Adapters** - Swappable implementations

### Code Examples

- [**Entities**](examples/entities.md) - Pure domain entities with business logic
- [**Value Objects**](examples/value-objects.md) - Age, Email, UUID validation
- [**CQRS**](examples/cqrs.md) - Commands and Queries with handlers
- [**Repositories**](examples/repositories.md) - Ports and Doctrine adapters

---

## 🛠️ Built With

- **[hexagonal-maker-bundle](https://github.com/ahmed-bhs/hexagonal-maker-bundle)** - Code generator
- **Symfony 6.4** - PHP framework
- **Doctrine ORM** - Database abstraction
- **Bootstrap 5** - UI components
- **Symfony Messenger** - CQRS bus

---

## 📚 Documentation

| Guide | Description |
|-------|-------------|
| [**Quick Start**](getting-started/quick-start.md) | Install and run in 5 minutes |
| [**Architecture Overview**](architecture/overview.md) | Understand the hexagonal structure |
| [**Domain Layer**](architecture/domain.md) | Pure entities and value objects |
| [**CQRS Examples**](examples/cqrs.md) | Commands and queries in action |
| [**Testing Guide**](guides/testing.md) | Run and write tests |
| [**Extending**](guides/extending.md) | Add new features |

---

## 🎯 Use Cases

This demo is perfect for:

- **Learning** hexagonal architecture
- **Teaching** DDD and CQRS patterns
- **Evaluating** hexagonal-maker-bundle
- **Prototyping** new projects
- **Job interviews** - showcase architecture skills

---

## 🤝 Contributing

This is a demo project. To contribute to the maker bundle:

→ [hexagonal-maker-bundle repository](https://github.com/ahmed-bhs/hexagonal-maker-bundle)

---

## 📄 License

MIT License - see [LICENSE](about/license.md)

---

<div align="center" markdown="1">

**Created by [Ahmed EBEN HASSINE](https://github.com/ahmed-bhs)**

[![View on GitHub](https://img.shields.io/badge/View-GitHub-181717?logo=github&style=for-the-badge)](https://github.com/ahmed-bhs/hexagonal-demo)
[![Try the Bundle](https://img.shields.io/badge/Try-Maker%20Bundle-6366F1?style=for-the-badge)](https://github.com/ahmed-bhs/hexagonal-maker-bundle)

</div>
