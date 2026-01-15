# Shared Kernel Architecture

## 🎯 Question : Domain peut-il dépendre de Shared ?

**Réponse courte :** OUI, mais **seulement de Shared/Domain**, pas de Shared/Infrastructure.

---

## 📊 Architecture finale (validée par Deptrac)

```
┌─────────────────────────────────────────────────────────┐
│                     SharedDomain                        │
│  (Concepts DDD purs : AggregateRoot, DomainEvent)       │
│  → Ne dépend de PERSONNE                                │
└────────────────────┬────────────────────────────────────┘
                     ↓
     ┌───────────────┴───────────────┐
     ↓                               ↓
┌────────────┐              ┌────────────────────┐
│   Domain   │              │ SharedInfrastructure│
│  (BC spec) │              │ (Adapters Symfony)  │
└─────┬──────┘              └──────────┬─────────┘
      ↓                                ↓
┌─────────────┐                        │
│ Application │                        │
└──────┬──────┘                        │
       ↓                               ↓
┌──────────────────────────────────────────┐
│          Infrastructure                  │
└──────────────┬───────────────────────────┘
               ↓
           ┌──────┐
           │  UI  │
           └──────┘
```

---

## 🔍 Shared/Domain vs Shared/Infrastructure

### ✅ Shared/Domain (pur - Domain PEUT dépendre)

```
Shared/Domain/
├── Aggregate/
│   └── AggregateRoot.php          # Pattern DDD (trait collecte événements)
├── Event/
│   └── DomainEvent.php            # Interface marker pour événements métier
├── Port/
│   ├── DomainEventPublisherInterface.php  # Port (interface)
│   ├── EventStoreInterface.php            # Port (interface)
│   └── IdGeneratorInterface.php           # Port (interface)
├── ValueObject/
│   └── Email.php                  # VO métier réutilisable
└── Validation/
    ├── ValidationError.php        # Représentation erreur métier
    ├── ValidationException.php    # Exception métier
    └── ValidatorInterface.php     # Port (interface)
```

**Caractéristiques :**
- ✅ **PHP pur** (zéro dépendance framework)
- ✅ **Concepts métier** (DDD patterns)
- ✅ **Ports uniquement** (pas d'implémentation)
- ✅ **Réutilisable** entre bounded contexts

**Pourquoi Domain PEUT dépendre ?**
- Ce sont des **abstractions métier**
- Définis dans le livre DDD (Eric Evans)
- Pas de couplage technique

### ❌ Shared/Infrastructure (technique - Domain NE PEUT PAS dépendre)

```
Shared/Infrastructure/
├── Event/
│   └── SymfonyDomainEventPublisher.php  # Adapter (implémente le port)
├── Persistence/
│   └── Doctrine/
│       ├── DomainEventPublisherListener.php
│       ├── DoctrineEventStore.php
│       └── Entity/StoredEvent.php
├── Generator/
│   └── UuidV7Generator.php              # Adapter (implémente IdGenerator)
├── Validation/
│   └── SymfonyValidatorAdapter.php      # Adapter (implémente Validator)
└── Http/
    └── EventListener/
        └── RequestIdListener.php
```

**Caractéristiques :**
- ❌ **Dépend de Symfony** (EventDispatcher, Doctrine, etc.)
- ❌ **Détails techniques** (comment, pas quoi)
- ❌ **Adapters** (implémentations concrètes des ports)

**Pourquoi Domain NE PEUT PAS dépendre ?**
- Violerait l'indépendance du Domain
- Créerait couplage à Symfony
- Contraire à l'architecture hexagonale

---

## 📋 Règles Deptrac

```yaml
SharedDomain: []                    # Ne dépend de PERSONNE

SharedInfrastructure:
  - SharedDomain                    # Implémente les ports

Domain:
  - SharedDomain                    # ✅ Concepts DDD partagés OK

Application:
  - Domain
  - SharedDomain                    # ✅ Ports OK

Infrastructure:
  - Domain
  - SharedDomain                    # ✅ Ports OK
  - SharedInfrastructure            # ✅ Adapters OK
```

---

## 🎯 Exemples concrets

### ✅ AUTORISÉ : Domain → SharedDomain

```php
// Domain/Model/DemandeCadeau.php
namespace App\Cadeau\Demande\Domain\Model;

use App\Shared\Domain\Aggregate\AggregateRoot;  // ✅ OK (concept DDD pur)
use App\Shared\Domain\Event\DomainEvent;        // ✅ OK (abstraction métier)

class DemandeCadeau
{
    use AggregateRoot;  // ✅ Pattern DDD, pas de dépendance technique
}
```

### ❌ INTERDIT : Domain → SharedInfrastructure

```php
// Domain/Model/DemandeCadeau.php
use App\Shared\Infrastructure\Event\SymfonyEventPublisher;  // ❌ VIOLATION
use App\Shared\Infrastructure\Persistence\DoctrineEventStore; // ❌ VIOLATION
```

### ✅ AUTORISÉ : Application → SharedDomain

```php
// Application/Command/SoumettreDemandeCadeauCommandHandler.php
use App\Shared\Domain\Port\DomainEventPublisherInterface;  // ✅ OK (port)
use App\Shared\Domain\Port\IdGeneratorInterface;           // ✅ OK (port)
```

### ✅ AUTORISÉ : Infrastructure → SharedInfrastructure

```php
// Infrastructure/EventSubscriber/GiftAttributedSubscriber.php
use App\Shared\Infrastructure\Event\SymfonyDomainEventPublisher;  // ✅ OK
```

---

## 🤔 Débat philosophique : Domain doit-il être 100% pur ?

### École puriste (Domain dépend de RIEN)

```
Domain = 0 dépendance (même pas SharedDomain)
```

**Arguments :**
- Domain vraiment isolé
- Chaque BC 100% autonome
- Philosophie hexagonale stricte

**Inconvénient :**
- Duplication (AggregateRoot dans chaque BC)
- Maintenance difficile

### École pragmatique (Domain → SharedDomain OK)

```
Domain → SharedDomain (concepts DDD purs uniquement)
```

**Arguments :**
- Shared Kernel = concept DDD officiel (Eric Evans)
- Évite duplication inutile
- SharedDomain est du code métier (pas technique)

**Condition :**
- SharedDomain doit rester **100% pur** (zéro framework)

---

## ✅ Notre choix : École pragmatique

**Pourquoi ?**

1. **Shared Kernel est DDD officiel**
   - Défini par Eric Evans dans le livre DDD
   - Concepts partagés entre bounded contexts

2. **SharedDomain est métier pur**
   ```php
   // C'est du métier, pas de la technique
   trait AggregateRoot { ... }
   interface DomainEvent { ... }
   ```

3. **Évite duplication inutile**
   - Pourquoi dupliquer AggregateRoot dans chaque BC ?
   - C'est exactement la même logique

4. **Reste hexagonal**
   - Domain ne dépend d'AUCUN framework
   - Domain ne dépend d'AUCUN détail technique
   - Domain dépend seulement de concepts métier partagés

---

## 📊 Comparaison finale

| Aspect | SharedDomain | SharedInfrastructure |
|--------|-------------|---------------------|
| **Nature** | Concepts métier | Détails techniques |
| **Dépendances** | Zéro (PHP pur) | Symfony, Doctrine, etc. |
| **Contenu** | Ports, Patterns DDD | Adapters, Implémentations |
| **Domain peut dépendre ?** | ✅ OUI | ❌ NON |
| **Exemples** | AggregateRoot, DomainEvent | SymfonyEventPublisher, DoctrineEventStore |

---

## 🎯 Règle d'or

> **Domain peut dépendre de SharedDomain SI ET SEULEMENT SI SharedDomain est 100% pur (zéro framework, zéro technique)**

Si vous ajoutez quelque chose de technique dans Shared, mettez-le dans `SharedInfrastructure`, pas `SharedDomain`.

---

## ✅ Validation

Cette architecture est **validée par Deptrac** :

```
Violations: 0 ✅
```

Toutes les dépendances respectent la règle :
- Domain → SharedDomain ✅
- Domain ↛ SharedInfrastructure ✅
- Infrastructure → SharedInfrastructure ✅
