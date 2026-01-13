# Analyse Shared Kernel - État Actuel

## 📊 État Actuel du Shared Kernel

### ✅ Ce qui EST dans Shared (correct)

**Domain Layer**
- `Shared/Domain/Port/IdGeneratorInterface` - ✅ Port pour génération d'IDs (utilisé par Attribution + Demande)
- `Shared/Domain/ValueObject/Email` - ✅ VO email (utilisé par Attribution + Demande)

**Infrastructure Layer**
- `Shared/Infrastructure/Generator/UuidV7Generator` - ✅ Implémentation UUID v7
- `Shared/Infrastructure/Persistence/Doctrine/Type/EmailType` - ✅ Type custom Doctrine pour Email

**Pagination (Sub-domain)**
- `Shared/Pagination/Domain/ValueObject/Page` - ✅ Pagination
- `Shared/Pagination/Domain/ValueObject/PerPage` - ✅ Pagination
- `Shared/Pagination/Domain/ValueObject/Total` - ✅ Pagination
- `Shared/Pagination/Domain/ValueObject/PaginatedResult` - ✅ Pagination

**Search (Sub-domain)**
- `Shared/Search/Domain/ValueObject/SearchTerm` - ✅ Recherche

---

## ❌ Ce qui N'EST PAS dans Shared (correct)

### Value Objects spécifiques à Attribution
- `Age` - ❌ NE DOIT PAS être dans Shared (spécifique au contexte Habitant)
- `HabitantId` - ❌ NE DOIT PAS être dans Shared (identité spécifique au contexte)

**Pourquoi ?**
- Ces VOs sont des concepts métier du bounded context "Attribution des cadeaux"
- Ils ne sont pas utilisés dans le contexte "Demande"
- Les déplacer vers Shared créerait un couplage inutile entre contextes

### Doctrine Types spécifiques
- `AgeType` - ❌ NE DOIT PAS être dans Shared (pour Age VO spécifique)
- `HabitantIdType` - ❌ NE DOIT PAS être dans Shared (pour HabitantId VO spécifique)

---

## 🧹 Nettoyage Potentiel : Méthodes Inutilisées

### Page.php
```php
// ❌ JAMAIS utilisées
public function next(): self { ... }       // 0 usages
public function previous(): self { ... }   // 0 usages
```

**Recommandation** : Supprimer jusqu'à besoin réel de pagination avec navigation prev/next

### Page.php, PerPage.php, Total.php
```php
// ❌ JAMAIS utilisées
public function equals(self $other): bool { ... }  // 0 usages
```

**Recommandation** : Supprimer `equals()` (déjà fait pour les autres VOs)

---

## 📋 Checklist : Qu'est-ce qui DEVRAIT être dans Shared ?

### ✅ Critères pour Shared Kernel

Un élément doit être dans Shared **SI ET SEULEMENT SI** :

1. ✅ **Utilisé par au moins 2 bounded contexts**
   - Email : ✅ (Attribution + Demande)
   - IdGeneratorInterface : ✅ (Attribution + Demande)
   - Age : ❌ (seulement Attribution)

2. ✅ **Concept générique non métier**
   - Pagination : ✅ (technique, réutilisable)
   - Search : ✅ (technique, réutilisable)
   - Email : ✅ (générique, pas spécifique à un métier)
   - Age : ❌ (concept métier spécifique à Habitant)

3. ✅ **Pas de logique métier spécifique**
   - IdGeneratorInterface : ✅ (infrastructure pure)
   - Email : ✅ (validation générique RFC)
   - Age : ❌ (logique métier : isAdult, isSenior, isChild)

---

## 🎯 Recommandations

### Haute Priorité : Nettoyer les méthodes inutilisées

```bash
# Supprimer de Page.php
- next()
- previous()
- equals()

# Supprimer de PerPage.php
- equals()

# Supprimer de Total.php
- equals()
```

**Impact** : ~15 lignes supprimées, moins de maintenance

### Basse Priorité : Veille sur l'évolution

**Surveiller** :
- Si un nouveau bounded context utilise Age → alors déplacer vers Shared
- Si Demande a besoin de HabitantId → alors déplacer vers Shared
- Pour l'instant, **laisser tel quel** (YAGNI)

---

## 📊 Statistiques Shared Kernel

| Catégorie | Fichiers | Statut |
|-----------|----------|--------|
| **Domain Ports** | 1 | ✅ Correct |
| **Domain Value Objects** | 1 | ✅ Correct |
| **Infrastructure Generators** | 1 | ✅ Correct |
| **Infrastructure Doctrine Types** | 1 | ✅ Correct |
| **Pagination Sub-domain** | 4 | ✅ Correct (nettoyage recommandé) |
| **Search Sub-domain** | 1 | ✅ Correct |

**Total** : 9 fichiers dans Shared

---

## ✅ Conclusion

Le Shared Kernel est **bien structuré** et respecte les principes DDD :

✅ **Pas de sur-partage** (Age et HabitantId restent dans leur contexte)
✅ **Partage justifié** (Email, IdGenerator utilisés par 2+ contextes)
✅ **Découplage** (Pagination et Search sont des sub-domains techniques)

**Seule amélioration** : Supprimer les méthodes inutilisées dans les VOs de Pagination (~15 lignes).

---

## 🚫 Anti-patterns à Éviter

### ❌ Ne PAS faire

```php
// ❌ Déplacer Age vers Shared "au cas où"
src/Shared/Domain/ValueObject/Age.php

// ❌ Créer un "CommonValueObjects" catch-all
src/Shared/Domain/ValueObject/Common/...

// ❌ Partager des entités entre contextes
src/Shared/Domain/Model/Habitant.php  // NON !
```

### ✅ Faire à la place

```php
// ✅ Chaque contexte garde ses propres concepts métier
src/Cadeau/Attribution/Domain/ValueObject/Age.php

// ✅ Shared = concepts vraiment génériques
src/Shared/Domain/ValueObject/Email.php

// ✅ Dupliquer plutôt que coupler
// Si 2 contextes ont un "Age" différent, créer 2 VOs distincts !
```

---

## 📚 Références

- **DDD Shared Kernel** : https://martinfowler.com/bliki/BoundedContext.html
- **Règle d'or** : "Duplication is far cheaper than the wrong abstraction"
- **Principe** : Le Shared Kernel doit rester **minimal** et **stable**
