# 🎯 Diagramme d'Architecture Interactif

## Vue d'ensemble

Ce projet inclut un **diagramme interactif** de l'architecture hexagonale utilisant **Cytoscape.js**, une puissante bibliothèque de visualisation de graphes.

## 🌐 Accès

### Page Dédiée
**URL**: http://127.0.0.1:8000/architecture

Une page complète dédiée à la visualisation de l'architecture avec :
- Diagramme interactif en plein écran
- Explications des principes clés
- Détails de chaque couche
- Validation Deptrac

### Page d'Accueil
Le diagramme est également intégré sur la page d'accueil : http://127.0.0.1:8000/

## 🎮 Fonctionnalités Interactives

### Navigation
- **Zoom** : Molette de la souris
- **Pan** : Cliquer-glisser sur le fond
- **Drag** : Déplacer les nœuds individuellement

### Interactions
- **Click sur un nœud** : Affiche les détails dans le panneau d'information
- **Click sur une flèche** : Affiche la relation de dépendance
- **Hover** : Effets visuels sur les éléments
- **Highlight** : Les éléments connectés sont mis en surbrillance

### Boutons
- **🔄 Reset** : Réinitialise le layout
- **🎯 Centrer** : Centre et adapte la vue

## 🎨 Légende

### Couleurs des Nœuds
- 🔵 **Bleu** - Domain (Cœur métier)
- 🔷 **Cyan** - Application (Use Cases)
- 🟢 **Vert** - Infrastructure (Adapters)
- 🟡 **Ambre** - UI (Présentation)
- 🟣 **Violet** - Symfony (Framework)

### Types de Flèches
- **Trait plein bleu** (→) - Dépend de
- **Trait pointillé vert** (⇢) - Implémente
- **Trait pointillé violet** (⋯) - Cas spécial (DataFixtures)

## 🛠️ Technologie

### Cytoscape.js
**Site officiel** : https://js.cytoscape.org/

**Pourquoi Cytoscape.js ?**
- ✅ Open-source et gratuit
- ✅ Excellent pour visualiser des architectures logicielles
- ✅ Animations fluides et performantes
- ✅ Interactions riches (zoom, drag, click)
- ✅ Styles CSS-like personnalisables
- ✅ Layouts automatiques (concentric, hierarchical, etc.)
- ✅ Pas de dépendances lourdes (via CDN)

### Layout Algorithm
Le diagramme utilise un layout **concentrique** où :
1. **Domain** est au centre (niveau 3)
2. **Application** et **Infrastructure** autour (niveau 2)
3. **UI** et **Symfony** en périphérie (niveau 1)

Cela reflète visuellement la hiérarchie de l'architecture hexagonale.

## 📦 Structure des Fichiers

```
templates/
├── components/
│   └── architecture_diagram.html.twig   # Composant réutilisable
└── architecture/
    └── index.html.twig                  # Page dédiée

src/Controller/
└── ArchitectureController.php           # Controller pour /architecture
```

## 🔧 Personnalisation

### Réutiliser le Composant

```twig
{% include 'components/architecture_diagram.html.twig' with {
    'title': 'Mon Titre Personnalisé'
} %}
```

### Modifier le Diagramme

Éditez `templates/components/architecture_diagram.html.twig` :

**Ajouter un nœud** :
```javascript
{ data: {
    id: 'mon-noeud',
    label: 'MON NOEUD',
    layer: 'custom',
    description: 'Description',
    info: 'Info supplémentaire'
}}
```

**Ajouter une relation** :
```javascript
{ data: {
    source: 'noeud-source',
    target: 'noeud-cible',
    label: 'label',
    type: 'depends',
    description: 'Description de la relation'
}}
```

**Styles disponibles** :
- `type: 'depends'` - Trait plein bleu
- `type: 'implements'` - Trait pointillé vert
- `type: 'special'` - Trait pointillé violet

## 🎓 En Savoir Plus

### Documentation Cytoscape.js
- **Getting Started** : https://js.cytoscape.org/#getting-started
- **API** : https://js.cytoscape.org/#core
- **Styles** : https://js.cytoscape.org/#style
- **Layouts** : https://js.cytoscape.org/#layouts
- **Events** : https://js.cytoscape.org/#events

### Alternatives Considérées
- **Vis.js** - Bon pour les réseaux physiques
- **Markmap** - Parfait pour les mind maps depuis Markdown
- **D3.js** - Plus flexible mais plus complexe
- **GoJS** - Commercial, très puissant mais payant

**Choix final** : Cytoscape.js pour son équilibre parfait entre puissance, simplicité et gratuité.

## 🚀 Prochaines Améliorations Possibles

1. **Animations** - Animer les flux de données
2. **Export** - Exporter le diagramme en PNG/SVG
3. **Filtres** - Filtrer par couche
4. **Zoom sur sous-graphe** - Zoom sur un module spécifique
5. **Mode édition** - Éditer le diagramme en temps réel
6. **Dark mode** - Thème sombre

## 📝 Notes

- Le composant charge Cytoscape.js depuis CDN (pas besoin de npm install)
- Compatible avec tous les navigateurs modernes
- Responsive et adaptatif
- Performance : gère facilement des centaines de nœuds
- Pas de conflit avec Tailwind CSS ou Turbo

## 🎉 Résultat

Un diagramme **vivant** de votre architecture qui aide à :
- ✅ Comprendre les dépendances
- ✅ Valider l'architecture hexagonale
- ✅ Former les nouveaux développeurs
- ✅ Documenter visuellement le projet
- ✅ Détecter les violations potentielles

**Explorez-le maintenant** : http://127.0.0.1:8000/architecture
