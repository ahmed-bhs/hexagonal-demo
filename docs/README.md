# Documentation - Hexagonal Architecture Demo

Cette documentation couvre l'architecture hexagonale complète du projet avec CQRS, DDD, et Event Sourcing.

---

## 📚 Documents disponibles

### 🏗️ Architecture générale
- **[README.md](../README.md)** - Vue d'ensemble du projet
- **[ARCHITECTURE_PURE_100.md](ARCHITECTURE_PURE_100.md)** - Analyse de la pureté architecturale (si existe)

### 🎯 Couche Application
- **[APPLICATION_LAYER_STRUCTURE.md](APPLICATION_LAYER_STRUCTURE.md)** ⭐ **MUST READ**
  - Structure complète de la couche Application
  - Quand utiliser Command vs Query vs Service vs DTO vs Exception
  - Matrix de décision
  - Best practices

### 🔄 Shared Kernel
- **[SHARED_KERNEL_ARCHITECTURE.md](SHARED_KERNEL_ARCHITECTURE.md)** ⭐ **IMPORTANT**
  - SharedDomain vs SharedInfrastructure
  - Domain peut-il dépendre de Shared ?
  - Règles de dépendances
  - Validé par Deptrac

### 🔐 Authentication & Security
- **[JWT_AUTHENTICATION_HEXAGONAL.md](JWT_AUTHENTICATION_HEXAGONAL.md)** ⭐ **NEW**
  - JWT authentication with hexagonal architecture
  - No Symfony Guard (uses Symfony Security Authenticator)
  - Pure Domain (no framework dependencies)
  - Complete CQRS implementation
  - Ports & Adapters pattern
  - API usage examples

### 🎨 UI Layer
- **[UI_LAYER_STRUCTURE.md](UI_LAYER_STRUCTURE.md)**
  - Structure de la couche UI
  - Controllers, Forms, Request DTOs, Presenters
  - What belongs in UI vs Application

### 📝 Refactoring
- **[REFACTORING_SUMMARY.md](REFACTORING_SUMMARY.md)**
  - Résumé du refactoring CQRS
  - Before/After structure
  - Changements effectués
  - Validation Deptrac

### 📊 Diagrammes
- **[architecture-dependencies.dot](architecture-dependencies.dot)**
  - Graphe des dépendances (format DOT)
  - Généré par Deptrac
  - Visualiser avec Graphviz : `dot -Tpng architecture-dependencies.dot -o architecture.png`

---

## 🎯 Par où commencer ?

### Pour comprendre l'architecture :
1. Lire [../README.md](../README.md) - Vue d'ensemble
2. Lire [SHARED_KERNEL_ARCHITECTURE.md](SHARED_KERNEL_ARCHITECTURE.md) - Base conceptuelle
3. Lire [APPLICATION_LAYER_STRUCTURE.md](APPLICATION_LAYER_STRUCTURE.md) - Structure détaillée

### Pour contribuer :
1. Comprendre la séparation Command/Query (CQRS)
2. Respecter les règles de dépendances (voir Deptrac)
3. Suivre les patterns dans APPLICATION_LAYER_STRUCTURE.md

---

## 🏗️ Architecture en un coup d'œil

```
┌─────────────────────────────────────────────────────────┐
│                     SharedDomain                        │
│  → AggregateRoot, DomainEvent, Ports (interfaces)       │
│  → Ne dépend de PERSONNE                                │
└────────────────────┬────────────────────────────────────┘
                     ↓
     ┌───────────────┴───────────────┐
     ↓                               ↓
┌────────────┐              ┌────────────────────┐
│   Domain   │              │ SharedInfrastructure│
│  - Model   │              │ - Adapters          │
│  - Event   │              │ - Persistence       │
│  - Port    │              │ - Event system      │
└─────┬──────┘              └──────────┬─────────┘
      ↓                                ↓
┌─────────────────┐                    │
│  Application    │ ◄──────────────────┘
│  - Command/     │  Write operations
│  - Query/       │  Read operations
│  - Service/     │  Orchestration
│  - DTO/         │  Data transfer
│  - Exception/   │  Business errors
└──────┬──────────┘
       ↓
┌──────────────────────────────────────────┐
│          Infrastructure                  │
│  - Persistence (Doctrine)                │
│  - EventSubscriber (Symfony)             │
│  - Messaging (Messenger)                 │
└──────────────┬───────────────────────────┘
               ↓
           ┌──────┐
           │  UI  │
           │ HTTP │
           │ CLI  │
           └──────┘
```

---

## 📊 Structure Application (CQRS)

```
Application/
├── Command/              # ✅ Write operations
│   ├── AttribuerCadeau/
│   │   ├── AttribuerCadeauCommand.php
│   │   ├── AttribuerCadeauCommandHandler.php
│   │   └── AttribuerCadeauCommandValidator.php
│   └── SoumettreDemandeCadeau/
│       ├── SoumettreDemandeCadeauCommand.php
│       └── SoumettreDemandeCadeauCommandHandler.php
│
├── Query/                # ✅ Read operations
│   ├── RecupererHabitants/
│   │   ├── RecupererHabitantsQuery.php
│   │   ├── RecupererHabitantsQueryHandler.php
│   │   └── RecupererHabitantsResponse.php
│   ├── RecupererCadeaux/
│   └── RecupererStatistiques/
│
├── Service/              # Orchestration complexe
│   └── AutomaticGiftAttributionService.php
│
├── DTO/                  # Data Transfer Objects
│   ├── AttributionResultDTO.php
│   ├── GiftDTO.php
│   ├── HabitantDTO.php
│   └── GiftRequestSummaryDTO.php
│
└── Exception/            # Business exceptions
    ├── NoEligibleGiftException.php
    ├── GiftAttributionFailedException.php
    └── InvalidDemandeCadeauException.php
```

---

## 🎯 Règles de dépendances (Deptrac)

| Couche | Peut dépendre de |
|--------|------------------|
| **SharedDomain** | Rien (0 dépendance) |
| **SharedInfrastructure** | SharedDomain |
| **Domain** | SharedDomain uniquement |
| **Application** | Domain + SharedDomain |
| **Infrastructure** | Domain + SharedDomain + SharedInfrastructure |
| **UI** | Application + Symfony |

**Validation :** `./vendor/bin/deptrac analyze`

**Résultat actuel :** ✅ **0 violations**

---

## 🔄 Flux d'exécution typique

### Use Case : Attribuer un cadeau

```
1. HTTP Request
   ↓
2. Controller (UI)
   - Crée AttribuerCadeauCommand
   ↓
3. CommandBus dispatch
   ↓
4. AttribuerCadeauCommandHandler (Application)
   - Validate
   - Load Habitant (via Port)
   - Load Cadeau (via Port)
   ↓
5. Attribution::createWithDetails() (Domain)
   - Business logic
   - recordThat(GiftAttributed) ← Événement enregistré
   ↓
6. Repository->save() (Infrastructure)
   - persist()
   - flush() ✅ Commit DB
   ↓
7. DomainEventPublisherListener (Infrastructure)
   - pullDomainEvents() ← Récupère GiftAttributed
   - eventPublisher->publish()
   ↓
8. EventStore + Symfony EventDispatcher
   - EventStore->append() (audit trail)
   - Dispatch to subscribers
   ↓
9. EventSubscribers (Infrastructure)
   - Send email (sync)
   - Dispatch PDF generation to Messenger (async)
   ↓
10. Response DTO → JSON
```

---

## ✅ Concepts DDD présents

| Concept | Exemple dans le projet |
|---------|------------------------|
| **Bounded Context** | Attribution, Demande |
| **Aggregate Root** | Attribution, DemandeCadeau, Habitant, Cadeau |
| **Entity** | Attribution, DemandeCadeau |
| **Value Object** | Age, Email, HabitantId |
| **Domain Event** | GiftAttributed, GiftRequestSubmitted |
| **Repository** | AttributionRepositoryInterface |
| **Port** | DomainEventPublisherInterface, EventStoreInterface |
| **Adapter** | DoctrineAttributionRepository, SymfonyDomainEventPublisher |
| **Shared Kernel** | SharedDomain (AggregateRoot, DomainEvent) |
| **CQRS** | Command/ vs Query/ |
| **Event Sourcing** | EventStore (append-only log) |

---

## 🚀 Commandes utiles

### Validation architecture
```bash
# Vérifier les dépendances
./vendor/bin/deptrac analyze

# Générer le graphique
./vendor/bin/deptrac analyze --formatter=graphviz-dot --output=docs/architecture.dot
dot -Tpng docs/architecture.dot -o docs/architecture.png
```

### Tests
```bash
# Tous les tests
vendor/bin/phpunit

# Tests unitaires Domain
vendor/bin/phpunit tests/Unit/

# Tests d'intégration
vendor/bin/phpunit tests/Integration/
```

### Linters
```bash
# PHP CS Fixer
vendor/bin/php-cs-fixer fix

# PHPStan
vendor/bin/phpstan analyse
```

---

## 📖 Ressources externes

### Livres
- **Domain-Driven Design** - Eric Evans (Blue Book)
- **Implementing Domain-Driven Design** - Vaughn Vernon (Red Book)
- **Clean Architecture** - Robert C. Martin

### Articles
- [Hexagonal Architecture - Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)
- [CQRS Pattern - Martin Fowler](https://martinfowler.com/bliki/CQRS.html)
- [Event Sourcing - Martin Fowler](https://martinfowler.com/eaaDev/EventSourcing.html)

### Outils
- [Deptrac](https://github.com/qossmic/deptrac) - Analyse de dépendances
- [Graphviz](https://graphviz.org/) - Visualisation de graphes

---

## 🤝 Contribution

Pour contribuer au projet :

1. **Respecter l'architecture hexagonale**
   - Domain ne dépend de rien (sauf SharedDomain)
   - Pas de framework dans Domain
   - Ports dans Domain, Adapters dans Infrastructure

2. **Suivre CQRS**
   - Commands dans `Application/Command/`
   - Queries dans `Application/Query/`
   - Service seulement si orchestration complexe

3. **Valider avec Deptrac**
   - Toujours vérifier : `./vendor/bin/deptrac analyze`
   - 0 violations requis

4. **Tests**
   - Tests unitaires pour Domain (pur)
   - Tests d'intégration pour Application
   - Tests fonctionnels pour Infrastructure/UI

---

## 📞 Contact

Pour questions ou suggestions :
- Issues GitHub
- Pull Requests bienvenues
- Documentation à améliorer ? Créez une issue !

---

**Version :** 2.0.0 (CQRS refactoring)
**Date :** 2026-01-15
**Status :** ✅ Production Ready
