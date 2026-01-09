# ⚙️ Configuration - hexagonal-demo

**Guide complet de configuration de l'application**

---

## 📋 Configurations Essentielles

### 1. Doctrine ORM

**Fichier**: `config/packages/doctrine.yaml`

#### Mapping YAML pour Architecture Hexagonale

```yaml
doctrine:
    orm:
        mappings:
            # Mapping par défaut (Attributes)
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App

            # ✅ Mapping YAML pour Module Hexagonal
            CadeauAttribution:
                type: yml
                is_bundle: false
                dir: '%kernel.project_dir%/src/Cadeau/Attribution/Infrastructure/Persistence/Doctrine/Orm/Mapping'
                prefix: 'App\Cadeau\Attribution\Domain\Model'
                alias: CadeauAttribution
```

**Pourquoi YAML ?**
- ✅ Domain reste 100% pur (zero dépendances Doctrine)
- ✅ Mapping dans Infrastructure (séparation parfaite)
- ✅ Facile à maintenir et modifier

**Fichiers de mapping**:
- `Habitant.orm.yml` - Mapping avec ValueObjects
- `Cadeau.orm.yml` - Mapping simple avec unique constraint
- `Attribution.orm.yml` - Mapping relation

---

### 2. Symfony Messenger (CQRS)

**Fichier**: `config/packages/messenger.yaml`

#### Deux Bus Séparés

```yaml
messenger:
    default_bus: command.bus

    buses:
        # Bus pour les Commands (Write operations)
        command.bus:
            middleware:
                - validation        # Valide les commandes
                - doctrine_transaction  # Transaction automatique

        # Bus pour les Queries (Read operations)
        query.bus:
            middleware:
                - validation        # Valide les queries
                # PAS de transaction pour lecture

    routing:
        # Commands → command.bus
        App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommand: command.bus

        # Queries → query.bus
        App\Cadeau\Attribution\Application\RecupererHabitants\RecupererHabitantsQuery: query.bus
```

**Avantages**:
- ✅ Séparation CQRS claire
- ✅ Transactions automatiques pour Commands
- ✅ Pas de transaction inutile pour Queries
- ✅ Validation automatique

---

### 3. Services (Dependency Injection)

**Fichier**: `config/services.yaml`

#### Exclusion des Entités Domain

```yaml
services:
    App\:
        resource: '../src/'
        exclude:
            - '../src/Entity/'
            - '../src/Kernel.php'
            # ✅ Exclure Domain Model & ValueObjects
            - '../src/Cadeau/Attribution/Domain/Model/'
            - '../src/Cadeau/Attribution/Domain/ValueObject/'
```

**Pourquoi ?**
- Les entités ne sont pas des services
- Les ValueObjects sont immutables
- Évite erreurs d'autowiring

#### Repository Bindings (Ports → Adapters)

```yaml
    # Dependency Inversion Pattern
    App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface:
        class: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\DoctrineHabitantRepository

    App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface:
        class: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\DoctrineCadeauRepository

    App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface:
        class: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\DoctrineAttributionRepository
```

**Avantages**:
- ✅ Domain dépend des interfaces (Ports)
- ✅ Infrastructure fournit les implémentations (Adapters)
- ✅ Facile de changer d'implémentation (InMemory pour tests, etc.)

#### Bus Injection

```yaml
    # Injecter le bon bus dans les controllers
    App\Cadeau\Attribution\UI\Http\Web\Controller\ListHabitantsController:
        arguments:
            $queryBus: '@query.bus'
```

---

## 🗄️ Base de Données

### Schéma Généré

```sql
-- Table habitant
CREATE TABLE habitant (
    id VARCHAR(36) PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE
);

-- Table cadeau
CREATE TABLE cadeau (
    id VARCHAR(36) PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    quantite INT NOT NULL
);

-- Table attribution
CREATE TABLE attribution (
    id VARCHAR(36) PRIMARY KEY,
    habitant_id VARCHAR(36) NOT NULL,
    cadeau_id VARCHAR(36) NOT NULL,
    date_attribution DATETIME NOT NULL
);
```

### Commandes Doctrine

```bash
# Créer la base
php bin/console doctrine:database:create

# Générer le schéma
php bin/console doctrine:schema:create

# ou avec migrations
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load
```

---

## 🎯 CQRS Pattern

### Command Bus (Write)

**Utilisation dans un service**:

```php
use Symfony\Component\Messenger\MessageBusInterface;

public function __construct(
    private readonly MessageBusInterface $commandBus  // ou command.bus
) {}

public function doSomething(): void
{
    $command = new AttribuerCadeauxCommand(
        habitantId: '...',
        cadeauId: '...'
    );

    // Dispatch avec transaction automatique
    $this->commandBus->dispatch($command);
}
```

### Query Bus (Read)

**Utilisation dans un controller**:

```php
use Symfony\Component\Messenger\Stamp\HandledStamp;

public function __construct(
    private readonly MessageBusInterface $queryBus
) {}

public function list(): Response
{
    $query = new RecupererHabitantsQuery();
    $envelope = $this->queryBus->dispatch($query);

    // Récupérer le résultat
    $handledStamp = $envelope->last(HandledStamp::class);
    $response = $handledStamp->getResult();

    return $this->render('...', [
        'habitants' => $response->habitants
    ]);
}
```

---

## 🔧 Environment Variables

**Fichier**: `.env` ou `.env.local`

```env
# Database
DATABASE_URL="mysql://user:password@127.0.0.1:3306/hexagonal_demo?serverVersion=8.0"
# ou PostgreSQL
# DATABASE_URL="postgresql://user:password@127.0.0.1:5432/hexagonal_demo?serverVersion=15"
# ou SQLite
# DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# Messenger Transport
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# App Environment
APP_ENV=dev
APP_SECRET=your-secret-here
```

---

## 🚀 Démarrage Rapide

### Installation Complète

```bash
# 1. Dépendances
composer install

# 2. Database
php bin/console doctrine:database:create
php bin/console doctrine:schema:create

# 3. Fixtures
php bin/console doctrine:fixtures:load

# 4. Serveur
symfony server:start
```

### Vérification

```bash
# Vérifier la config Doctrine
php bin/console doctrine:mapping:info

# Vérifier les routes
php bin/console debug:router

# Vérifier les services
php bin/console debug:container HabitantRepositoryInterface

# Vérifier Messenger
php bin/console debug:messenger
```

---

## 📊 Architecture des Fichiers

### Structure Complète

```
config/
├── packages/
│   ├── doctrine.yaml           ✅ Mapping YAML configuré
│   └── messenger.yaml          ✅ CQRS buses configurés
├── services.yaml               ✅ Repository bindings configurés
└── routes.yaml

src/Cadeau/Attribution/
├── Domain/
│   ├── Model/                  ← Entities (pure PHP)
│   ├── ValueObject/            ← ValueObjects (immutable)
│   └── Port/                   ← Interfaces (contracts)
├── Application/
│   ├── AttribuerCadeaux/       ← Command + Handler
│   └── RecupererHabitants/     ← Query + Handler + Response
├── Infrastructure/
│   └── Persistence/Doctrine/
│       ├── Orm/Mapping/        ✅ YAML mappings
│       └── *.Repository.php    ← Adapters (implémentations)
└── UI/
    └── Http/Web/Controller/    ← Controllers
```

---

## ✅ Checklist de Vérification

### Configuration

- [x] Doctrine mapping YAML configuré
- [x] Messenger buses séparés (command.bus / query.bus)
- [x] Repository bindings dans services.yaml
- [x] Domain Model exclu de l'autowiring
- [x] Fichiers .orm.yml complétés

### Base de Données

- [x] DATABASE_URL configuré dans .env
- [x] Base de données créée
- [x] Schéma généré
- [x] Fixtures chargées

### Application

- [x] Routes fonctionnelles (/, /habitants, /cadeaux)
- [x] Controllers injectent les bons bus
- [x] Handlers enregistrés automatiquement
- [x] Templates Twig existent

---

## 🐛 Troubleshooting

### Erreur "Entity not found"

```bash
# Vérifier les mappings
php bin/console doctrine:mapping:info

# Recréer le schéma
php bin/console doctrine:schema:drop --force
php bin/console doctrine:schema:create
```

### Erreur "Handler not found"

```bash
# Vérifier que le handler a bien #[AsMessageHandler]
# Vérifier que le handler est dans src/ (autowiring)
# Clear cache
php bin/console cache:clear
```

### Erreur "Repository not found"

```bash
# Vérifier services.yaml
php bin/console debug:container HabitantRepositoryInterface

# La classe d'implémentation doit être retournée
```

---

## 🎓 Bonnes Pratiques

### 1. Toujours Utiliser les Interfaces

❌ **Mauvais**:
```php
public function __construct(
    private DoctrineHabitantRepository $repository  // Implémentation concrète
) {}
```

✅ **Bon**:
```php
public function __construct(
    private HabitantRepositoryInterface $repository  // Interface
) {}
```

### 2. Séparer Command et Query

❌ **Mauvais**:
```php
// Tout dans un seul bus
$messageBus->dispatch($command);
$messageBus->dispatch($query);
```

✅ **Bon**:
```php
// Bus séparés
$commandBus->dispatch($command);  // Avec transaction
$queryBus->dispatch($query);      // Sans transaction
```

### 3. Mapping YAML, pas Attributes

❌ **Mauvais** (dans Domain):
```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Habitant { ... }  // Domain couplé à Doctrine
```

✅ **Bon**:
```php
// Domain pur
class Habitant { ... }

// Mapping dans Infrastructure/Persistence/Doctrine/Orm/Mapping/Habitant.orm.yml
```

---

## 📚 Documentation Complémentaire

- [README.md](README.md) - Vue d'ensemble du projet
- [QUICKSTART.md](QUICKSTART.md) - Démarrage en 5 minutes
- [AMELIORATIONS-APPLIQUEES.md](AMELIORATIONS-APPLIQUEES.md) - Détails des améliorations

---

**Date**: 2026-01-08
**Auteur**: Ahmed + Claude
**Version**: 1.0.0
