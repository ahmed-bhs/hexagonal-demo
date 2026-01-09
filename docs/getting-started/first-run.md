# ⚡ Quick Start - hexagonal-demo

**Démarrer l'application en 5 minutes**

---

## 📦 Installation Express

```bash
# 1. Installation
composer install

# 2. Database
php bin/console doctrine:database:create
php bin/console doctrine:schema:create

# 3. Fixtures
php bin/console doctrine:fixtures:load --no-interaction

# 4. Start
symfony server:start -d
```

**✅ Accéder à** : http://localhost:8000

---

## 🎯 Points d'Entrée

### Page d'Accueil
```
http://localhost:8000/
```
**Dashboard avec statistiques**

### Liste des Habitants
```
http://localhost:8000/habitants
```
**10 habitants pré-configurés** (enfants, adultes, seniors)

### Catalogue des Cadeaux
```
http://localhost:8000/cadeaux
```
**10 cadeaux** avec gestion de stock

---

## 🔍 Explorer le Code

### Domain Layer (Pure PHP)

```php
// Entity avec Factory
src/Cadeau/Attribution/Domain/Model/Cadeau.php

// ValueObject avec Validation
src/Cadeau/Attribution/Domain/ValueObject/Email.php

// Repository Interface (Port)
src/Cadeau/Attribution/Domain/Port/HabitantRepositoryInterface.php
```

### Application Layer (Use Cases)

```php
// Command Handler
src/Cadeau/Attribution/Application/AttribuerCadeaux/
    AttribuerCadeauxCommandHandler.php

// Query Handler + Response
src/Cadeau/Attribution/Application/RecupererHabitants/
    RecupererHabitantsQueryHandler.php
    RecupererHabitantsResponse.php
```

### Infrastructure Layer (Adapters)

```php
// Doctrine Repository
src/Cadeau/Attribution/Infrastructure/Persistence/Doctrine/
    DoctrineHabitantRepository.php
```

### UI Layer (Controllers)

```php
// Web Controller
src/Cadeau/Attribution/UI/Http/Web/Controller/
    ListHabitantsController.php
```

---

## 🧪 Tester l'Application

### Via Web Interface

1. Accéder au dashboard → Voir les statistiques
2. Cliquer "Voir la liste" → Liste des 10 habitants
3. Cliquer "Voir le catalogue" → Catalogue des 10 cadeaux

### Via Console

```bash
# Vérifier les habitants en base
php bin/console doctrine:query:sql "SELECT * FROM habitant"

# Vérifier les cadeaux
php bin/console doctrine:query:sql "SELECT * FROM cadeau"

# Vérifier les attributions
php bin/console doctrine:query:sql "SELECT * FROM attribution"
```

---

## 🎨 Personnaliser

### Ajouter un Habitant

```php
// Créer un fichier test
$habitant = Habitant::create(
    'Jean',
    'Dupont',
    new Age(25),
    new Email('jean.dupont@example.com')
);

$habitantRepository->save($habitant);
```

### Ajouter un Cadeau

```php
$cadeau = Cadeau::create(
    'Nouveau Cadeau',
    'Description du cadeau',
    10  // quantité
);

$cadeauRepository->save($cadeau);
```

### Attribuer un Cadeau

```php
$command = new AttribuerCadeauxCommand(
    habitantId: $habitant->getId()->value,
    cadeauId: $cadeau->getId()
);

$commandBus->dispatch($command);
```

---

## 🐛 Dépannage

### Erreur "Database doesn't exist"

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

### Erreur "No fixtures loaded"

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

### Port 8000 déjà utilisé

```bash
symfony server:start -d --port=8001
```

### Vider le cache

```bash
php bin/console cache:clear
```

---

## 📖 Aller Plus Loin

### Documentation Complète
- Lire [README.md](README.md) pour l'architecture détaillée
- Voir [AMELIORATIONS-APPLIQUEES.md](AMELIORATIONS-APPLIQUEES.md) pour les améliorations

### Générer du Code

```bash
# Nouvelle entité
php bin/console make:hexagonal:entity cadeau/attribution NouvelleEntite \
  --properties="nom:string,description:text"

# Nouvelle commande
php bin/console make:hexagonal:command cadeau/attribution NouvelleAction \
  --properties="param1:string,param2:int"

# Nouveau controller
php bin/console make:hexagonal:controller cadeau/attribution NouveauController /route
```

### Bundle Documentation
https://github.com/ahmed-bhs/hexagonal-maker-bundle

---

## ✅ Checklist de Vérification

- [ ] Serveur démarré sur http://localhost:8000
- [ ] Dashboard s'affiche correctement
- [ ] 10 habitants visibles dans `/habitants`
- [ ] 10 cadeaux visibles dans `/cadeaux`
- [ ] Statistiques correctes sur le dashboard
- [ ] Aucune erreur dans les logs

---

**🎉 Félicitations ! L'application fonctionne !**

**Next steps**: Explorer le code, modifier les entités, ajouter des fonctionnalités.
