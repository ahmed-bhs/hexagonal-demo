# 🔍 Deptrac - Validation de l'Architecture Hexagonale

## Qu'est-ce que Deptrac ?

**Deptrac** est un outil d'analyse statique qui valide que votre code respecte bien les règles d'architecture définies. Il détecte les violations de dépendances entre les différentes couches de votre application.

## 🎯 Règles d'Architecture Hexagonale

Notre configuration Deptrac valide les règles suivantes :

### ✅ Dépendances Autorisées

```yaml
Domain: []                              # ❌ Ne dépend de PERSONNE
Application: [Domain]                   # ✅ Peut dépendre de Domain uniquement
Infrastructure: [Domain]                # ✅ Peut dépendre de Domain uniquement
UI: [Application, Symfony]              # ✅ Peut dépendre d'Application et Symfony
Symfony: [Domain, Application, Infrastructure]  # ✅ Peut utiliser Domain (DataFixtures), Application, Infrastructure
```

**Note importante** : Symfony peut accéder à Domain **uniquement pour les DataFixtures** (données de test).
Tous les Controllers passent par Application (CQRS) et ne dépendent jamais directement de Domain.

### Flux de Dépendances

```
         ┌─────────────────┐
         │     Symfony     │ (peut tout utiliser)
         └────────┬────────┘
                  │
         ┌────────▼────────┐
         │       UI        │
         └────────┬────────┘
                  │
         ┌────────▼────────┐
         │   Application   │
         └────────┬────────┘
                  │
         ┌────────▼────────┐
         │     Domain      │ (ne dépend de rien)
         └────────▲────────┘
                  │
         ┌────────┴────────┐
         │ Infrastructure  │
         └─────────────────┘
```

### ❌ Violations Détectées

Si Deptrac trouve des violations, c'est que le code viole les règles :
- Domain qui dépend d'autre chose ❌
- Application qui dépend d'Infrastructure ou UI ❌
- Infrastructure qui dépend d'Application ou UI ❌
- UI qui dépend directement de Domain ou Infrastructure ❌

## 📋 Commandes Disponibles

### 1. Analyse Standard
```bash
composer deptrac
```
Analyse les dépendances et affiche les violations.

### 2. Analyse avec Détails
```bash
composer deptrac:uncovered
```
Affiche également les dépendances non couvertes par les règles.

### 3. Génération de Graphique
```bash
composer deptrac:graph
```
Génère un graphique visuel `deptrac-graph.png` montrant les dépendances entre couches.

### 4. Commande Directe
```bash
vendor/bin/deptrac analyze [options]
```

## 📊 Interpréter les Résultats

### Exemple de Violation

```
DependsOnDisallowedLayer
App\Cadeau\Attribution\Application\AttribuerCadeauxCommandHandler
must not depend on
App\Cadeau\Attribution\Infrastructure\SomeRepository

You are depending on token that is a part of a layer that you are not allowed to depend on. (Infrastructure)
/home/ahmed/Projets/hexagonal-demo/src/Cadeau/Attribution/Application/AttribuerCadeauxCommandHandler.php:24
```

**Explication :**
- La couche Application essaie d'utiliser directement une classe de la couche Infrastructure
- Solution : Utiliser une interface (Port) dans le Domain au lieu d'une implémentation concrète

### Types de Messages

- **DependsOnDisallowedLayer** : Violation d'une règle d'architecture
- **Uncovered** : Dépendance non gérée par les règles (peut être normale, ex: Symfony\Component\Messenger)
- **Warnings** : Avertissements qui ne bloquent pas l'analyse
- **Errors** : Erreurs de configuration

## 🏗️ Couches Définies

### 1. **Domain** (Cœur Métier)
```
App\Cadeau\*\Domain\*
```
- Entités (Habitant, Cadeau, Attribution)
- ValueObjects (Age, Email, HabitantId)
- Ports (Interfaces de repositories)
- ❌ Aucune dépendance externe autorisée

### 2. **Application** (Cas d'Usage - CQRS)
```
App\Cadeau\*\Application\*
```
- Commands & CommandHandlers
- Queries & QueryHandlers
- Responses (DTOs)
- ✅ Peut dépendre uniquement de Domain

### 3. **Infrastructure** (Adaptateurs)
```
App\Cadeau\*\Infrastructure\*
```
- Repositories Doctrine
- Custom Types Doctrine
- Persistence (ORM Mappings)
- ✅ Peut dépendre uniquement de Domain

### 4. **UI** (Présentation)
```
App\Cadeau\*\UI\*
```
- Controllers
- Forms
- Templates (référencés)
- ✅ Peut dépendre de Application et Symfony

### 5. **Symfony** (Framework)
```
App\Controller\*
App\DataFixtures\*
App\Kernel
```
- Controllers globaux
- Fixtures
- Configuration
- ✅ Peut dépendre de tout

## 🔧 Configuration

Le fichier de configuration est `deptrac.yaml` à la racine du projet.

### Structure de base

```yaml
deptrac:
  paths:
    - ./src

  layers:
    - name: Domain
      collectors:
        - type: classLike
          value: App\\Cadeau\\.*\\Domain\\.*

  ruleset:
    Domain:
      - Application    # Interdiction
      - Infrastructure # Interdiction
      - UI             # Interdiction
      - Symfony        # Interdiction
```

**Important :** Dans le `ruleset`, on liste les dépendances **INTERDITES**, pas les autorisées !

## 🎨 Visualisation

Pour générer un graphique visuel :

```bash
composer deptrac:graph
```

Cela créera `deptrac-graph.png` montrant :
- Les couches (Domain, Application, Infrastructure, UI, Symfony)
- Les dépendances autorisées (flèches vertes)
- Les violations (flèches rouges)

## 🚀 Intégration CI/CD

Ajoutez Deptrac à votre pipeline CI :

```yaml
# .github/workflows/architecture.yml
- name: Validate Architecture
  run: composer deptrac
```

L'analyse échouera si des violations sont détectées.

## 📚 Documentation Officielle

- [Deptrac GitHub](https://github.com/qossmic/deptrac)
- [Documentation Deptrac](https://qossmic.github.io/deptrac/)

## ✨ Résultat Attendu

```
Report
Violations           0
Skipped violations   0
Uncovered            X  (acceptable)
Allowed              Y
Warnings             0
Errors               0
```

**0 Violations** = ✅ Architecture hexagonale parfaitement respectée !

## 🎉 État Actuel

```bash
composer deptrac
```

```
Report
Violations           0    ✅
Skipped violations   0
Uncovered            111
Allowed              88
Warnings             0
Errors               0
```

**Notre application respecte à 100% les principes de l'architecture hexagonale !**
