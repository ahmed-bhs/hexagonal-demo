# Hexagonal Demo - Gestion des Cadeaux

Application de démonstration de l'architecture hexagonale avec Symfony

## Prérequis

**Principe D (Dependency Inversion)** : Les dépendances pointent vers l'intérieur. Le Domain définit les interfaces (Ports), l'Infrastructure les implémente (Adapters).

**Règle** : Domain ne dépend de rien. Application dépend du Domain. Infrastructure implémente les Ports du Domain.

**Concrètement** : Seule l'Infrastructure dépend de Symfony et Doctrine. Domain et Application sont en PHP pur.

---

## Table des Matières

1. [Introduction](#1-introduction)
2. [Architecture](#2-architecture)
3. [Installation](#3-installation)
4. [Utilisation](#4-utilisation)
5. [Structure du Projet](#5-structure-du-projet)
6. [Tests](#6-tests)
7. [Documentation](#7-documentation)

---

## 1. Introduction

### 1.1 Contexte

Cette application illustre l'implémentation d'une architecture hexagonale (Ports & Adapters) avec Symfony. Elle utilise le bundle [hexagonal-maker-bundle](https://github.com/ahmed-bhs/hexagonal-maker-bundle) pour générer automatiquement la structure du code.

### 1.2 Domaine Métier

Le système gère la distribution de cadeaux aux habitants selon les règles suivantes :

- Gestion d'habitants avec leurs caractéristiques (âge, email)
- Catalogue de cadeaux avec gestion de stock
- Attribution de cadeaux aux habitants
- Demandes de cadeaux avec workflow d'approbation

### 1.3 Patterns Appliqués

- **Architecture Hexagonale** : Séparation Domain / Application / Infrastructure
- **Domain-Driven Design** : Entities, Value Objects, Repositories
- **CQRS** : Séparation Commands / Queries
- **Dependency Inversion** : Interfaces (Ports) définies dans le Domain

---

## 2. Architecture

### 2.1 Structure Hexagonale

Le projet suit une structure en couches concentriques :

```
Domain (centre)
  → Application (use cases)
    → Infrastructure (adapters)
      → UI (primary adapters)
```

### 2.2 Organisation du Code

```
src/Cadeau/
├── Attribution/                                    # Bounded Context 1 : Gestion attribution cadeaux
│   ├── Domain/                                     # Logique métier pure (aucune dépendance)
│   │   ├── Model/                                  # Entities DDD (identité + cycle de vie, pur PHP sans Doctrine)
│   │   │   ├── Attribution.php                     # Représente l'attribution d'un cadeau à un habitant
│   │   │   ├── Cadeau.php                          # Représente un cadeau avec stock
│   │   │   └── Habitant.php                        # Représente un habitant avec ses caractéristiques
│   │   ├── Port/                                   # Interfaces (contrats) définies par le Domain
│   │   │   ├── AttributionRepositoryInterface.php  # Port pour persistance des attributions
│   │   │   ├── CadeauRepositoryInterface.php       # Port pour persistance des cadeaux
│   │   │   └── HabitantRepositoryInterface.php     # Port pour persistance des habitants
│   │   └── ValueObject/                            # Objets immuables définis par leurs valeurs
│   │       ├── Age.php                             # Encapsule l'âge avec règles métier (adulte, senior)
│   │       └── HabitantId.php                      # Identifiant typé pour Habitant
│   ├── Application/                                # Use Cases (orchestration Domain)
│   │   ├── AttribuerCadeaux/                       # Use Case : Attribuer un cadeau
│   │   │   ├── AttribuerCadeauxCommand.php         # DTO d'entrée (write operation)
│   │   │   ├── AttribuerCadeauxCommandHandler.php  # Orchestration logique métier
│   │   │   └── AttribuerCadeauxCommandValidator.php # Validation pure PHP (UUID)
│   │   ├── RecupererCadeaux/                       # Use Case : Lister les cadeaux
│   │   │   ├── RecupererCadeauxQuery.php           # DTO d'entrée (read operation)
│   │   │   ├── RecupererCadeauxQueryHandler.php    # Lecture sans modification
│   │   │   └── RecupererCadeauxResponse.php        # DTO de sortie
│   │   ├── RecupererHabitants/                     # Use Case : Lister les habitants (pagination, recherche)
│   │   │   ├── RecupererHabitantsQuery.php         # DTO avec critères pagination/recherche
│   │   │   ├── RecupererHabitantsQueryHandler.php  # Lecture avec pagination
│   │   │   └── RecupererHabitantsResponse.php      # DTO avec résultats paginés
│   │   └── RecupererStatistiques/                  # Use Case : Statistiques globales
│   │       ├── RecupererStatistiquesQuery.php      # DTO d'entrée
│   │       ├── RecupererStatistiquesQueryHandler.php # Agrégation données
│   │       └── RecupererStatistiquesResponse.php   # DTO avec stats (nombre habitants, cadeaux, etc.)
│   ├── Infrastructure/                             # Adapters (implémentations concrètes)
│   │   └── Persistence/Doctrine/                   # Adapter pour persistance via Doctrine ORM
│   │       ├── DoctrineAttributionRepository.php   # Implémente AttributionRepositoryInterface
│   │       ├── DoctrineCadeauRepository.php        # Implémente CadeauRepositoryInterface
│   │       ├── DoctrineHabitantRepository.php      # Implémente HabitantRepositoryInterface
│   │       ├── Orm/Mapping/                        # Mapping Doctrine XML (externalisé du Domain)
│   │       │   ├── Attribution.orm.xml             # Mapping Attribution Entity → DB
│   │       │   ├── Cadeau.orm.xml                  # Mapping Cadeau Entity → DB
│   │       │   └── Habitant.orm.xml                # Mapping Habitant Entity → DB
│   │       └── Type/                               # Types Doctrine custom pour Value Objects
│   │           ├── AgeType.php                     # Mapping Age (VO) → int (DB)
│   │           └── HabitantIdType.php              # Mapping HabitantId (VO) → string (DB)
│   └── UI/Http/Web/Controller/                     # Primary Adapters (points d'entrée HTTP)
│       ├── ListCadeauxController.php               # Contrôleur affichage liste cadeaux
│       └── ListHabitantsController.php             # Contrôleur affichage liste habitants
│
├── Demande/                                        # Bounded Context 2 : Gestion demandes cadeaux
│   ├── Domain/                                     # Logique métier pure
│   │   ├── Model/
│   │   │   └── DemandeCadeau.php                   # Entity DDD (pur PHP sans Doctrine)
│   │   └── Port/
│   │       └── DemandeCadeauRepositoryInterface.php # Port pour persistance des demandes
│   ├── Application/                                # Use Cases
│   │   └── SoumettreDemandeCadeau/                 # Use Case : Soumettre une demande de cadeau
│   │       ├── SoumettreDemandeCadeauCommand.php   # DTO avec données demande (nom, email, téléphone, motivation)
│   │       └── SoumettreDemandeCadeauCommandHandler.php # Orchestration création demande + validation Symfony
│   ├── Infrastructure/                             # Adapters
│   │   └── Persistence/Doctrine/
│   │       ├── DoctrineDemandeCadeauRepository.php # Implémente DemandeCadeauRepositoryInterface
│   │       └── Orm/Mapping/
│   │           └── DemandeCadeau.orm.xml           # Mapping DemandeCadeau Entity → DB
│   └── UI/Http/Web/                                # Primary Adapters
│       ├── Controller/
│       │   └── DemandeCadeauFormController.php     # Contrôleur formulaire soumission demande
│       └── Form/
│           └── DemandeCadeauType.php               # Type de formulaire Symfony
│
└── Shared/                                         # Shared Kernel : Éléments partagés entre contextes
    ├── Domain/                                     # Concepts métier partagés
    │   ├── Port/
    │   │   └── IdGeneratorInterface.php            # Port pour génération IDs (UUID v7, ULID, etc.)
    │   ├── Validation/                             # Validation hexagonale
    │   │   ├── ValidationError.php                 # Représente une erreur de validation (field + message)
    │   │   ├── ValidationException.php             # Exception levée lors d'échec validation
    │   │   └── ValidatorInterface.php              # Port pour validation (2 implémentations : PHP pur, Symfony)
    │   └── ValueObject/
    │       └── Email.php                           # VO Email (utilisé par Attribution et Demande)
    ├── Infrastructure/                             # Adapters partagés
    │   ├── Generator/
    │   │   └── UuidV7Generator.php                 # Implémente IdGeneratorInterface (UUID v7 time-ordered)
    │   ├── Persistence/Doctrine/Type/
    │   │   └── EmailType.php                       # Type Doctrine pour Email VO
    │   └── Validation/
    │       └── SymfonyValidatorAdapter.php         # Adapte Symfony Validator à ValidatorInterface
    ├── Pagination/                                 # Pagination réutilisable
    │   └── Domain/ValueObject/
    │       ├── Page.php                            # Numéro de page
    │       ├── PaginatedResult.php                 # Résultat paginé (items + metadata)
    │       ├── PerPage.php                         # Nombre items par page
    │       └── Total.php                           # Nombre total d'items
    └── Search/                                     # Recherche réutilisable
        └── Domain/ValueObject/
            └── SearchTerm.php                      # Terme de recherche validé
```

### 2.3 Flux de Données

Le flux d'exécution suit le pattern suivant :

```
Requête HTTP
  ↓
Controller (UI Layer)
  ↓
Command/Query (Application Layer)
  ↓
Handler (Application Layer)
  ↓
Domain Model (Domain Layer)
  ↓
Repository Interface (Domain Port)
  ↓
Repository Implementation (Infrastructure Adapter)
  ↓
Base de données
```

### 2.4 Dépendances

Les dépendances suivent la règle de dépendance vers l'intérieur :

- **Domain** : Aucune dépendance externe (PHP pur)
- **Application** : Dépend uniquement du Domain
- **Infrastructure** : Implémente les ports du Domain
- **UI** : Utilise Application et Infrastructure

---

## 3. Installation

### 3.1 Prérequis

- PHP 8.1 ou supérieur
- Composer 2.x
- Symfony CLI
- Base de données compatible Doctrine (MySQL, PostgreSQL, SQLite)

### 3.2 Procédure d'Installation

#### Étape 1 : Clonage du Dépôt

```bash
git clone https://github.com/ahmed-bhs/symfony-hexagonal-architecture-demo.git
cd symfony-hexagonal-architecture-demo
```

#### Étape 2 : Installation des Dépendances

```bash
composer install
```

#### Étape 3 : Configuration de la Base de Données

Éditer le fichier `.env` et configurer la variable `DATABASE_URL` :

```bash
DATABASE_URL="mysql://user:password@127.0.0.1:3306/hexagonal_demo"
```

#### Étape 4 : Création de la Base de Données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

#### Étape 5 : Chargement des Données de Test

```bash
php bin/console doctrine:fixtures:load
```

#### Étape 6 : Démarrage du Serveur

```bash
symfony server:start
```

L'application est accessible à l'adresse : `http://localhost:8000`

---

## 4. Utilisation

### 4.1 Interface Web

#### Page d'Accueil

Route : `/`

Affiche un dashboard avec :
- Statistiques générales (nombre d'habitants, cadeaux, attributions)
- Répartition des habitants par catégorie d'âge
- Liste récente des attributions

#### Liste des Habitants

Route : `/habitants`

Fonctionnalités :
- Pagination (10 habitants par page)
- Recherche par nom, prénom ou email
- Affichage des informations : nom, prénom, âge, email
- Catégorisation : Enfant (< 18 ans), Adulte (18-64 ans), Senior (≥ 65 ans)

#### Catalogue des Cadeaux

Route : `/cadeaux`

Affiche :
- Liste des cadeaux disponibles
- État du stock (disponible / rupture de stock)
- Description de chaque cadeau

### 4.2 Ligne de Commande

```bash
# Lister les habitants
php bin/console app:list-habitants

# Charger les fixtures
php bin/console doctrine:fixtures:load --no-interaction
```

### 4.3 Utilisation Programmatique

#### Dispatcher une Commande

```php
use App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommand;

$command = new AttribuerCadeauxCommand(
    habitantId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
    cadeauId: 'a3bb189e-8bf9-3888-9912-ace4e6543002'
);

$this->commandBus->dispatch($command);
```

#### Dispatcher une Query

```php
use App\Cadeau\Attribution\Application\RecupererHabitants\RecupererHabitantsQuery;
use Symfony\Component\Messenger\Stamp\HandledStamp;

$query = new RecupererHabitantsQuery(
    page: 1,
    perPage: 10,
    searchTerm: ''
);

$envelope = $this->queryBus->dispatch($query);
$response = $envelope->last(HandledStamp::class)->getResult();

foreach ($response->habitants as $habitant) {
    // Traitement
}
```

---

## 5. Structure du Projet

### 5.1 Répertoires Principaux

| Répertoire | Description |
|------------|-------------|
| `src/Cadeau/Attribution/` | Bounded context pour l'attribution de cadeaux |
| `src/Cadeau/Demande/` | Bounded context pour les demandes de cadeaux |
| `src/Shared/` | Shared Kernel (éléments partagés entre contextes) |
| `tests/` | Tests unitaires, intégration et fonctionnels |
| `config/` | Configuration de l'application |
| `templates/` | Templates Twig |

### 5.2 Configuration

#### Doctrine

Fichier : `config/packages/doctrine.yaml`

Configuration des mappings XML et types custom :

```yaml
doctrine:
    dbal:
        types:
            habitant_id: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\Type\HabitantIdType
            age: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\Type\AgeType
            email_vo: App\Shared\Infrastructure\Persistence\Doctrine\Type\EmailType
    orm:
        mappings:
            CadeauAttribution:
                type: xml
                dir: '%kernel.project_dir%/src/Cadeau/Attribution/Infrastructure/Persistence/Doctrine/Orm/Mapping'
                prefix: App\Cadeau\Attribution\Domain\Model
```

#### Validation

Fichier : `config/packages/validator.yaml`

Configuration pour charger les contraintes YAML (approche hexagonale) :

```yaml
framework:
    validation:
        mapping:
            paths:
                - '%kernel.project_dir%/config/validator'
```

Fichier : `config/validator/demande_cadeau_command.yaml`

Contraintes de validation externalisées (NotBlank, Email, Length, Regex) :

```yaml
App\Cadeau\Demande\Application\SoumettreDemandeCadeau\SoumettreDemandeCadeauCommand:
    properties:
        emailDemandeur:
            - NotBlank: ~
            - Email: ~
```

#### Services

Fichier : `config/services.yaml`

Binding des ports aux adapters :

```yaml
services:
    # ID Generation Port (Shared)
    App\Shared\Domain\Port\IdGeneratorInterface:
        class: App\Shared\Infrastructure\Generator\UuidV7Generator

    # Repository Ports
    App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface:
        class: App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine\DoctrineHabitantRepository

    # Validation Ports
    # Validateur pur PHP (pour AttribuerCadeauxCommand)
    App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommandHandler:
        arguments:
            $validator: '@App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommandValidator'

    # Adaptateur Symfony Validator (pour commands avec contraintes YAML)
    App\Cadeau\Demande\Application\SoumettreDemandeCadeau\SoumettreDemandeCadeauCommandHandler:
        arguments:
            $validator: '@App\Shared\Infrastructure\Validation\SymfonyValidatorAdapter'
```

### 5.3 Conventions de Nommage

- **Entities** : Nom au singulier (ex: `Habitant.php`)
- **Value Objects** : Nom descriptif (ex: `Age.php`, `Email.php`)
- **Commands** : Verbe à l'infinitif + nom (ex: `AttribuerCadeauxCommand.php`)
- **Queries** : Verbe + nom (ex: `RecupererHabitantsQuery.php`)
- **Handlers** : Nom de la commande/query + `Handler` (ex: `AttribuerCadeauxCommandHandler.php`)
- **Repositories** : Nom de l'entité + `Repository` (ex: `DoctrineHabitantRepository.php`)

---

## 6. Tests

### 6.1 Pyramide de Tests

Le projet suit la pyramide de tests classique :

```
      E2E (5%)
     /        \
    /  Func.   \
   /   (10%)    \
  /______________\
 /                \
/  Integration     \
/     (20%)         \
/____________________\
/                    \
/    Unit Tests       \
/       (65%)          \
/______________________\
```

### 6.2 Types de Tests

#### Tests Unitaires (Unit)

Emplacement : `tests/Unit/`

Couvrent :
- Value Objects (Age, Email, HabitantId)
- Entities (Cadeau, DemandeCadeau)
- Logique métier pure

Exécution :
```bash
vendor/bin/phpunit tests/Unit/
```

#### Tests d'Intégration (Integration)

Emplacement : `tests/Integration/`

Couvrent :
- Handlers avec repositories InMemory
- Orchestration Application → Domain

Exécution :
```bash
vendor/bin/phpunit tests/Integration/
```

#### Tests Fonctionnels (Functional)

Emplacement : `tests/Functional/`

Couvrent :
- Configuration du kernel Symfony
- Injection de dépendances
- Configuration des buses de messages

Exécution :
```bash
vendor/bin/phpunit tests/Functional/
```

### 6.3 Exécution des Tests

```bash
# Tous les tests
vendor/bin/phpunit

# Avec rapport détaillé
vendor/bin/phpunit --testdox

# Avec couverture (nécessite Xdebug)
vendor/bin/phpunit --coverage-html coverage/
```

### 6.4 Résultats

Au moment de la rédaction :
- **31 tests** exécutés
- **51 assertions**
- **100% de réussite**
- Temps d'exécution : ~149ms

---

## 7. Documentation

### 7.1 Documentation Technique

- `ARCHITECTURE_PURE_100.md` : Analyse de la pureté architecturale
- `docs/ARCHITECTURE_UUID_V7.md` : Migration vers UUID v7
- `docs/ANALYSE_PRINCIPES_VIOLATION.md` : Analyse des violations YAGNI/DRY/SoC/SOLID
- `docs/ANALYSE_SHARED_KERNEL.md` : Documentation du Shared Kernel
- `docs/TESTS_COMPLETS.md` : Vue d'ensemble de la suite de tests
- `docs/TESTS_DOMAIN.md` : Documentation des tests du domaine
- `tests/PYRAMIDE_TESTS_HEXAGONAL.md` : Guide de la pyramide de tests

### 7.2 Concepts Clés

#### Architecture Hexagonale

L'architecture hexagonale isole le domaine métier des détails techniques. Les dépendances pointent toujours vers l'intérieur :

- **Domain** : Contient la logique métier pure
- **Application** : Orchestre les use cases
- **Infrastructure** : Implémente les détails techniques
- **UI** : Points d'entrée de l'application

#### Domain-Driven Design

Patterns DDD utilisés :

- **Entities** : Objets avec identité (Habitant, Cadeau, Attribution)
- **Value Objects** : Objets définis par leurs attributs (Age, Email, HabitantId)
- **Repositories** : Abstraction de la persistance
- **Bounded Contexts** : Attribution et Demande
- **Shared Kernel** : Éléments partagés (Email, IdGenerator, Pagination)

#### CQRS

Séparation stricte entre :

- **Commands** : Opérations d'écriture (création, modification, suppression)
- **Queries** : Opérations de lecture (consultation, recherche)

Chaque opération a son propre handler dédié.

### 7.3 Principes Appliqués

- **SOLID** : Respect des 5 principes de conception objet
- **DRY** : Pas de duplication de code
- **YAGNI** : Seulement le code nécessaire
- **SoC** : Séparation claire des responsabilités

---

## Annexes

### A. Références

- [Architecture Hexagonale - Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)
- [Domain-Driven Design - Eric Evans](https://domainlanguage.com/ddd/)
- [CQRS Pattern - Martin Fowler](https://martinfowler.com/bliki/CQRS.html)
- [Symfony Documentation](https://symfony.com/doc/current/index.html)

### B. Glossaire

- **Port** : Interface définissant un contrat
- **Adapter** : Implémentation concrète d'un port
- **Bounded Context** : Frontière dans laquelle un modèle est défini
- **Value Object** : Objet immuable défini par ses attributs
- **Entity** : Objet avec identité et cycle de vie
- **Aggregate** : Cluster d'objets traités comme une unité

### C. Licence

MIT License - Voir fichier LICENSE

### D. Auteur

Ahmed EBEN HASSINE
Email : ahmedbhs123@gmail.com
GitHub : https://github.com/ahmed-bhs

Date : Janvier 2026
Version : 1.0.0
