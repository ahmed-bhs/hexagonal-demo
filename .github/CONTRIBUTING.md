# Contributing to Hexagonal Demo

Merci de votre intérêt pour contribuer à ce projet de démonstration ! 🎉

## 📋 Table des Matières

- [Code de Conduite](#code-de-conduite)
- [Comment Contribuer](#comment-contribuer)
- [Standards de Code](#standards-de-code)
- [Architecture](#architecture)
- [Tests](#tests)

## 🤝 Code de Conduite

Ce projet respecte un code de conduite basé sur le respect mutuel et l'inclusion. Soyez respectueux, constructif et bienveillant dans vos interactions.

## 💡 Comment Contribuer

### Rapporter un Bug

1. Vérifiez que le bug n'a pas déjà été signalé dans les [Issues](https://github.com/ahmed-bhs/hexagonal-demo/issues)
2. Créez une nouvelle issue avec le template "Bug Report"
3. Décrivez clairement le problème avec des étapes pour le reproduire
4. Incluez votre environnement (PHP version, OS, etc.)

### Proposer une Fonctionnalité

1. Vérifiez que la fonctionnalité n'existe pas déjà
2. Créez une issue pour discuter de votre idée
3. Attendez les retours avant de commencer le développement
4. Soumettez une Pull Request avec votre implémentation

### Soumettre une Pull Request

1. Forkez le repository
2. Créez une branche depuis `main`:
   ```bash
   git checkout -b feature/ma-fonctionnalite
   ```
3. Committez vos changements avec des messages clairs:
   ```bash
   git commit -m "feat: Ajouter support pour X"
   ```
4. Poussez vers votre fork:
   ```bash
   git push origin feature/ma-fonctionnalite
   ```
5. Ouvrez une Pull Request vers `main`

## 📐 Standards de Code

### Architecture Hexagonale

Ce projet suit **strictement** l'architecture hexagonale. Toute contribution doit respecter:

1. **Domain** (Cœur métier)
   - Aucune dépendance externe
   - Logique métier pure
   - Entités, ValueObjects, Ports (interfaces)

2. **Application** (Use Cases)
   - CQRS: Commands/Queries + Handlers
   - Dépend uniquement du Domain
   - Orchestration de la logique métier

3. **Infrastructure** (Adapters)
   - Implémente les Ports du Domain
   - Technologies concrètes (Doctrine, etc.)
   - Aucune logique métier

4. **UI** (Présentation)
   - Controllers, Forms, Templates
   - Dépend uniquement de l'Application
   - Pas d'accès direct à l'Infrastructure

### Validation Deptrac

Toute PR doit passer la validation Deptrac:

```bash
composer deptrac
```

**Aucune violation n'est acceptée.**

### Style de Code

Nous utilisons les standards Symfony:

```bash
# Vérifier le style
composer cs-check

# Fixer automatiquement
composer cs-fix
```

### Commits Conventionnels

Utilisez les [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bug
- `docs:` Documentation uniquement
- `style:` Formatage, sans changement de code
- `refactor:` Refactoring sans changement de comportement
- `test:` Ajout ou modification de tests
- `chore:` Tâches de maintenance

Exemples:
```
feat: Add event-driven architecture support
fix: Correct Age validation for edge cases
docs: Update README with Docker setup
```

## 🏗️ Architecture

### Ajouter une Nouvelle Entité

1. Créez l'entité dans `Domain/Model/`
2. Ajoutez les ValueObjects nécessaires
3. Créez le Port (interface) dans `Domain/Port/`
4. Implémentez l'Adapter Doctrine dans `Infrastructure/`
5. Ajoutez le mapping XML
6. Créez les Use Cases dans `Application/`
7. Ajoutez les Controllers dans `UI/`

### Structure des Dossiers

```
src/[Context]/[BoundedContext]/
├── Domain/
│   ├── Model/           # Entités
│   ├── ValueObject/     # Value Objects
│   └── Port/            # Interfaces
├── Application/
│   └── [UseCase]/       # Commands/Queries + Handlers
├── Infrastructure/
│   └── Persistence/     # Adapters
└── UI/
    └── Http/Web/        # Controllers, Forms
```

## 🧪 Tests

### Exécuter les Tests

```bash
# Tous les tests
composer test

# Tests unitaires uniquement
composer test:unit

# Tests d'intégration
composer test:integration

# Avec couverture
composer test:coverage
```

### Écrire des Tests

1. **Tests Unitaires**: Testez les entités, ValueObjects, logique métier
2. **Tests d'Intégration**: Testez les Handlers avec repositories mockés
3. **Tests Fonctionnels**: Testez les Controllers end-to-end

Exemple:
```php
// tests/Unit/Domain/ValueObject/AgeTest.php
class AgeTest extends TestCase
{
    public function testValidAge(): void
    {
        $age = new Age(25);
        $this->assertEquals(25, $age->getValue());
        $this->assertTrue($age->isAdult());
    }

    public function testInvalidAge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Age(-5);
    }
}
```

## 📝 Documentation

Toute nouvelle fonctionnalité doit être documentée:

1. Commentaires PHPDoc dans le code
2. README mis à jour si nécessaire
3. Exemples d'utilisation
4. Diagrammes si architecture modifiée

## 🚀 Processus de Review

1. Les PR sont reviewées par les mainteneurs
2. Le code doit respecter tous les standards
3. Les tests doivent passer (CI/CD)
4. Deptrac doit valider l'architecture
5. Au moins 1 approbation requise

## 💬 Questions ?

- Ouvrez une [Discussion](https://github.com/ahmed-bhs/hexagonal-demo/discussions)
- Rejoignez-nous sur [Twitter](https://twitter.com/ahmed_bhs)
- Consultez la [Documentation](README.md)

## 📜 Licence

En contribuant, vous acceptez que vos contributions soient sous licence MIT.

---

Merci de contribuer à améliorer cette démonstration de l'architecture hexagonale ! 🙏
