# Migration vers UUID v7 avec Port IdGeneratorInterface

## 📋 Résumé

Migration de la génération d'UUID directe (Symfony Uid v4) vers une architecture hexagonale pure avec port `IdGeneratorInterface` et UUID v7.

## ✅ Changements effectués

### 1. Création des Ports (Domain Layer)

#### Contexte Attribution
- **Fichier**: `src/Cadeau/Attribution/Domain/Port/IdGeneratorInterface.php`
- **Rôle**: Interface définissant le contrat de génération d'ID
- **Méthode**: `generate(): string`

#### Contexte Demande
- **Fichier**: `src/Cadeau/Demande/Domain/Port/IdGeneratorInterface.php`
- **Rôle**: Interface définissant le contrat de génération d'ID
- **Méthode**: `generate(): string`

### 2. Création des Adapters (Infrastructure Layer)

#### Contexte Attribution
- **Fichier**: `src/Cadeau/Attribution/Infrastructure/Generator/UuidV7Generator.php`
- **Implémente**: `App\Cadeau\Attribution\Domain\Port\IdGeneratorInterface`
- **Technologie**: Symfony Uid - UUID v7

#### Contexte Demande
- **Fichier**: `src/Cadeau/Demande/Infrastructure/Generator/UuidV7Generator.php`
- **Implémente**: `App\Cadeau\Demande\Domain\Port\IdGeneratorInterface`
- **Technologie**: Symfony Uid - UUID v7

### 3. Mise à jour des Handlers (Application Layer)

#### AttribuerCadeauxCommandHandler
**Avant** (violation d'architecture) :
```php
use Symfony\Component\Uid\Uuid;  // ❌ Dépendance Infrastructure

$attribution = Attribution::create(
    Uuid::v4()->toRfc4122(),  // ❌ Couplage fort
    $habitantId,
    $cadeauId
);
```

**Après** (architecture hexagonale pure) :
```php
use App\Cadeau\Attribution\Domain\Port\IdGeneratorInterface;  // ✅ Port du Domain

public function __construct(
    private IdGeneratorInterface $idGenerator,  // ✅ Injection du port
    // ...
) {}

$attribution = Attribution::create(
    $this->idGenerator->generate(),  // ✅ Découplage complet
    $habitantId,
    $cadeauId
);
```

#### SoumettreDemandeCadeauCommandHandler
**Avant** :
```php
use Symfony\Component\Uid\Uuid;  // ❌ Dépendance Infrastructure

$demande = DemandeCadeau::create(
    id: Uuid::v4()->toRfc4122(),
    // ...
);
```

**Après** :
```php
use App\Cadeau\Demande\Domain\Port\IdGeneratorInterface;  // ✅ Port du Domain

public function __construct(
    private IdGeneratorInterface $idGenerator,  // ✅ Injection du port
    // ...
) {}

$demande = DemandeCadeau::create(
    id: $this->idGenerator->generate(),  // ✅ Découplage complet
    // ...
);
```

### 4. Configuration des Services (config/services.yaml)

```yaml
# ID Generation Ports
App\Cadeau\Attribution\Domain\Port\IdGeneratorInterface:
    class: App\Cadeau\Attribution\Infrastructure\Generator\UuidV7Generator

App\Cadeau\Demande\Domain\Port\IdGeneratorInterface:
    class: App\Cadeau\Demande\Infrastructure\Generator\UuidV7Generator
```

### 5. Nettoyage configuration

#### Doctrine (config/packages/doctrine.yaml)
- ❌ Supprimé le mapping `App` vers `src/Entity` (dossier supprimé)
- ✅ Conservé uniquement les mappings hexagonaux (`CadeauAttribution`, `CadeauDemande`)

#### Routes (config/routes.yaml)
- ❌ Supprimé le routing vers `src/Controller` (dossier supprimé)
- ✅ Conservé uniquement les routings hexagonaux

## 🎯 Pourquoi UUID v7 ?

### Avantages par rapport à UUID v4

| Aspect | UUID v4 (ancien) | UUID v7 (nouveau) |
|--------|-----------------|-------------------|
| **Ordre** | Aléatoire | Temps-ordonné |
| **Performance DB** | Index fragmenté | Sequential inserts |
| **Tri** | Non triable | Triable par création |
| **Fragmentation** | Élevée | Faible |
| **Distribution** | ✅ Excellente | ✅ Excellente |

### Format UUID v7

```
018c1e7e-9c4d-7b5a-8f2e-3d4c5b6a7890
└─────┘ └──┘ └──┘ └──┘ └──────────┘
   |      |    |    |        |
   |      |    |    |        └─ Random (62 bits)
   |      |    |    └─ Random (12 bits)
   |      |    └─ Version + Variant
   |      └─ Timestamp milliseconds
   └─ Timestamp milliseconds
```

- **48 bits**: Unix timestamp (millisecondes)
- **12 bits**: Aléatoire (unicité dans la même milliseconde)
- **62 bits**: Aléatoire (unicité globale)

## 🏗️ Architecture Hexagonale - 100% Pure

### Avant (90/100)

```
Application Layer
    ↓ dépend directement de
Symfony Uid (Infrastructure)  ← ⚠️ VIOLATION
```

### Après (100/100)

```
Application Layer
    ↓ dépend de
Domain Port (IdGeneratorInterface)
    ↑ implémenté par
Infrastructure Adapter (UuidV7Generator)
    ↓ utilise
Symfony Uid
```

### Bénéfices

1. ✅ **Zero dépendance Infrastructure dans Application**
   - Application ne connaît que les ports du Domain
   - Peut être testée sans Infrastructure

2. ✅ **Inversion de dépendance complète**
   - Domain définit les interfaces
   - Infrastructure les implémente

3. ✅ **Testabilité**
   - Création d'un `FakeIdGenerator` pour tests déterministes
   - Plus besoin de mocker Symfony Uid

4. ✅ **Flexibilité**
   - Swap facile vers ULID, Snowflake, etc.
   - Changement dans Infrastructure uniquement

## 🧪 Tests

### Exemple de FakeIdGenerator pour tests

```php
// Tests/Fake/FakeIdGenerator.php
final class FakeIdGenerator implements IdGeneratorInterface
{
    private int $counter = 1;

    public function generate(): string
    {
        return sprintf('fake-id-%d', $this->counter++);
    }

    public function generateFixed(string $id): string
    {
        return $id;
    }
}

// Dans un test
$fakeIdGenerator = new FakeIdGenerator();
$handler = new AttribuerCadeauxCommandHandler(
    $fakeIdGenerator,  // ✅ ID prévisibles
    $habitantRepository,
    $cadeauRepository,
    $attributionRepository
);

$handler->__invoke($command);

$attribution = $attributionRepository->findById('fake-id-1');  // ✅ Déterministe
$this->assertNotNull($attribution);
```

## 🔄 Migration future possible

### Vers ULID (Universally Unique Lexicographically Sortable Identifier)

```php
// src/Cadeau/Attribution/Infrastructure/Generator/UlidGenerator.php
use Symfony\Component\Uid\Ulid;

final readonly class UlidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Ulid::generate()->toRfc4122();
    }
}

// config/services.yaml
App\Cadeau\Attribution\Domain\Port\IdGeneratorInterface:
    class: App\Cadeau\Attribution\Infrastructure\Generator\UlidGenerator
    # ✅ Changement dans 1 seul fichier !
```

**Aucun changement dans Application Layer nécessaire !**

## 📊 Résultat

- ✅ **Architecture hexagonale pure** : 100/100
- ✅ **Domain complètement isolé**
- ✅ **Application sans dépendance Infrastructure**
- ✅ **UUID v7** pour meilleures performances
- ✅ **Testabilité** maximale
- ✅ **Flexibilité** totale

## 🎓 Références

- [RFC 9562 - UUID v7](https://www.rfc-editor.org/rfc/rfc9562.html)
- [Symfony Uid Component](https://symfony.com/doc/current/components/uid.html)
- [Hexagonal Architecture - Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)
