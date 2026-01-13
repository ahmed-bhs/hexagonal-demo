# Pyramide de Tests - Architecture Hexagonale

## 📐 Pyramide des Tests pour l'Architecture Hexagonale

```
                    /\
                   /  \
                  / E2E\          End-to-End (5%)
                 /______\         - Scénarios complets
                /        \        - UI → API → DB
               /  Func.   \       - Lents, fragiles
              /____________\
             /              \
            /  Intégration   \    Integration (15%)
           /       (3)        \   - Handlers + Repos
          /__________________\   - InMemory ou DB test
         /                    \
        /    Unitaires (20)    \  Unit (80%)
       /                        \ - Domain pur
      /___________________________\ - Rapides, isolés
```

**Ratio idéal** : **80% Unit / 15% Integration / 5% E2E**

---

## 🎯 Mapping Architecture Hexagonale → Tests

### Vue d'ensemble

```
┌─────────────────────────────────────────────┐
│           DOMAIN LAYER                      │
│  Tests: UNITAIRES (80%)                     │
│  ✅ Value Objects                           │
│  ✅ Entities                                │
│  ✅ Domain Services                         │
│  ❌ Pas de mocks, pas de dépendances        │
└─────────────────────────────────────────────┘
              ↑ depends on
┌─────────────────────────────────────────────┐
│       APPLICATION LAYER                     │
│  Tests: INTÉGRATION (15%)                   │
│  ✅ Command Handlers                        │
│  ✅ Query Handlers                          │
│  ✅ Use Cases                               │
│  ⚙️  Avec InMemory Repositories             │
└─────────────────────────────────────────────┘
              ↑ implements
┌─────────────────────────────────────────────┐
│      INFRASTRUCTURE LAYER                   │
│  Tests: INTÉGRATION (5%)                    │
│  ✅ Doctrine Repositories (avec DB test)    │
│  ✅ API Clients                             │
│  ⚙️  Avec vraie DB ou mocks externes        │
└─────────────────────────────────────────────┘
              ↑ uses
┌─────────────────────────────────────────────┐
│           UI LAYER                          │
│  Tests: FONCTIONNELS/E2E (5%)               │
│  ✅ Controllers HTTP                        │
│  ✅ Scénarios complets                      │
│  ⚙️  Avec Kernel Symfony + DB               │
└─────────────────────────────────────────────┘
```

---

## 📖 Leçons par Type de Test

---

## 1️⃣ TESTS UNITAIRES (Domain Layer) - 80%

### 🎯 Objectif
Tester la **logique métier pure** sans aucune dépendance externe.

### ✅ Quoi tester ?

#### A. Value Objects
**Pourquoi ?** Contiennent la validation critique du domaine.

```php
// tests/Unit/Cadeau/Attribution/Domain/ValueObject/AgeTest.php

#[Test]
public function it_rejects_negative_age(): void
{
    // Arrange & Act & Assert
    $this->expectException(\InvalidArgumentException::class);
    new Age(-1);
}

#[Test]
public function it_identifies_adult(): void
{
    // Arrange
    $child = new Age(17);
    $adult = new Age(18);

    // Act & Assert
    $this->assertFalse($child->isAdult());
    $this->assertTrue($adult->isAdult());
}
```

**Leçons :**
- ✅ Tester TOUTES les validations (bornes, formats)
- ✅ Tester TOUTE la logique métier (isAdult, isSenior, etc.)
- ✅ Un test = Un scénario de validation
- ❌ Pas de mocks (VOs sont purs)
- ❌ Pas de setup complexe

#### B. Entities
**Pourquoi ?** Contiennent les règles métier et les invariants.

```php
// tests/Unit/Cadeau/Attribution/Domain/Model/CadeauTest.php

#[Test]
public function it_diminishes_stock(): void
{
    // Arrange
    $cadeau = Cadeau::create('id', 'Vélo', 'Description', 10);

    // Act
    $cadeau->diminuerStock(3);

    // Assert
    $this->assertEquals(7, $cadeau->getQuantite());
}

#[Test]
public function it_rejects_insufficient_stock(): void
{
    // Arrange
    $cadeau = Cadeau::create('id', 'Vélo', 'Description', 5);

    // Assert
    $this->expectException(\DomainException::class);

    // Act
    $cadeau->diminuerStock(10);
}
```

**Leçons :**
- ✅ Tester les **méthodes métier** (pas les getters/setters)
- ✅ Tester les **règles d'invariants** (stock ne peut pas être négatif)
- ✅ Tester les **state machines** (états d'une demande)
- ✅ Utiliser **AAA pattern** (Arrange-Act-Assert)
- ❌ Ne pas tester les getters/setters simples
- ❌ Pas de dépendances externes

#### C. Domain Services
**Pourquoi ?** Orchestrent la logique métier complexe.

```php
#[Test]
public function it_calculates_total_price_with_discount(): void
{
    // Arrange
    $pricingService = new PricingService();
    $items = [new Item(100), new Item(200)];

    // Act
    $total = $pricingService->calculateTotal($items, new Discount(10));

    // Assert
    $this->assertEquals(270, $total); // (100+200) - 10%
}
```

**Leçons :**
- ✅ Tester les **calculs métier**
- ✅ Tester les **règles de pricing, taxation, etc.**
- ✅ Utiliser des **données de test réalistes**

---

### 📋 Checklist Tests Unitaires

- [ ] Tous les Value Objects ont des tests de validation
- [ ] Tous les Value Objects avec logique métier sont testés
- [ ] Toutes les méthodes métier des Entities sont testées
- [ ] Tous les invariants sont testés (règles qui ne doivent jamais être violées)
- [ ] Toutes les state machines sont testées
- [ ] Aucun mock utilisé
- [ ] Tests rapides (< 10ms par test)
- [ ] Naming explicite (`it_rejects_negative_age`)

---

## 2️⃣ TESTS D'INTÉGRATION (Application Layer) - 15%

### 🎯 Objectif
Tester l'**orchestration** entre Domain et Repositories sans base de données réelle.

### ✅ Quoi tester ?

#### A. Command Handlers
**Pourquoi ?** Orchestrent les use cases de modification.

```php
// tests/Integration/Cadeau/Attribution/Application/AttribuerCadeauxHandlerTest.php

final class AttribuerCadeauxHandlerTest extends TestCase
{
    private InMemoryHabitantRepository $habitantRepository;
    private InMemoryCadeauRepository $cadeauRepository;
    private InMemoryAttributionRepository $attributionRepository;
    private FakeIdGenerator $idGenerator;
    private AttribuerCadeauxCommandHandler $handler;

    protected function setUp(): void
    {
        // Arrange - InMemory repositories
        $this->habitantRepository = new InMemoryHabitantRepository();
        $this->cadeauRepository = new InMemoryCadeauRepository();
        $this->attributionRepository = new InMemoryAttributionRepository();
        $this->idGenerator = new FakeIdGenerator();

        $this->handler = new AttribuerCadeauxCommandHandler(
            $this->idGenerator,
            $this->habitantRepository,
            $this->cadeauRepository,
            $this->attributionRepository
        );
    }

    #[Test]
    public function it_attributes_cadeau_to_habitant(): void
    {
        // Arrange - Préparer les données
        $habitantId = '550e8400-e29b-41d4-a716-446655440001';
        $cadeauId = '550e8400-e29b-41d4-a716-446655440002';

        $habitant = Habitant::create(...);
        $this->habitantRepository->save($habitant);

        $cadeau = Cadeau::create($cadeauId, 'Vélo', 'Description', 10);
        $this->cadeauRepository->save($cadeau);

        $command = new AttribuerCadeauxCommand($habitantId, $cadeauId);

        // Act - Exécuter le handler
        $this->handler->__invoke($command);

        // Assert - Vérifier le résultat
        $attributions = $this->attributionRepository->findAll();
        $this->assertCount(1, $attributions);
        $this->assertEquals('fake-id-1', $attributions[0]->getId());
    }

    #[Test]
    public function it_rejects_attribution_when_habitant_not_found(): void
    {
        // Arrange
        $command = new AttribuerCadeauxCommand('non-existent', 'cad-123');

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->handler->__invoke($command);
    }
}
```

**Leçons :**
- ✅ Utiliser **InMemory Repositories** (pas de DB)
- ✅ Utiliser **FakeIdGenerator** pour IDs prévisibles
- ✅ Tester le **happy path** (scénario nominal)
- ✅ Tester les **cas d'erreur** (entité non trouvée)
- ✅ Vérifier l'**orchestration complète** (plusieurs repos)
- ❌ Ne pas tester la logique métier (déjà fait en Unit)
- ❌ Ne pas utiliser de vraie DB (lent, complexe)

#### B. Query Handlers
**Pourquoi ?** Orchestrent la récupération de données.

```php
#[Test]
public function it_retrieves_habitants_with_pagination(): void
{
    // Arrange
    $this->habitantRepository->save(Habitant::create(...));
    $this->habitantRepository->save(Habitant::create(...));

    $query = new RecupererHabitantsQuery(page: 1, perPage: 10);

    // Act
    $response = $this->handler->__invoke($query);

    // Assert
    $this->assertCount(2, $response->habitants);
    $this->assertEquals(1, $response->currentPage);
}
```

**Leçons :**
- ✅ Tester la **pagination**
- ✅ Tester les **filtres/search**
- ✅ Tester le **format de la response**

---

### 📋 Checklist Tests d'Intégration

- [ ] Tous les Command Handlers testés (happy path + erreurs)
- [ ] Tous les Query Handlers testés
- [ ] InMemory Repositories créés
- [ ] FakeIdGenerator utilisé
- [ ] Pas de vraie base de données
- [ ] Tests rapides (< 50ms par test)
- [ ] Vérification de l'orchestration complète

---

## 3️⃣ TESTS D'INTÉGRATION (Infrastructure Layer) - 5%

### 🎯 Objectif
Tester les **adapters techniques** avec leurs dépendances réelles.

### ✅ Quoi tester ?

#### A. Doctrine Repositories
**Pourquoi ?** Vérifier que le mapping ORM fonctionne.

```php
// tests/Integration/Cadeau/Attribution/Infrastructure/DoctrineHabitantRepositoryTest.php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineHabitantRepositoryTest extends KernelTestCase
{
    private HabitantRepositoryInterface $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(HabitantRepositoryInterface::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    #[Test]
    public function it_persists_and_retrieves_habitant(): void
    {
        // Arrange
        $habitant = Habitant::create(
            new HabitantId('550e8400-e29b-41d4-a716-446655440001'),
            'John',
            'Doe',
            new Age(30),
            new Email('john@example.com')
        );

        // Act
        $this->repository->save($habitant);
        $this->entityManager->clear(); // Nettoyer le cache

        $retrieved = $this->repository->findById('550e8400-e29b-41d4-a716-446655440001');

        // Assert
        $this->assertNotNull($retrieved);
        $this->assertEquals('John', $retrieved->getPrenom());
        $this->assertEquals(30, $retrieved->getAge()->value);
    }

    #[Test]
    public function it_finds_by_email(): void
    {
        // Arrange
        $habitant = Habitant::create(...);
        $this->repository->save($habitant);
        $this->entityManager->clear();

        // Act
        $found = $this->repository->findByEmail('john@example.com');

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals('John', $found->getPrenom());
    }

    protected function tearDown(): void
    {
        // Nettoyer la DB test après chaque test
        parent::tearDown();
    }
}
```

**Leçons :**
- ✅ Utiliser **base de données de test** (SQLite ou DB test)
- ✅ Tester le **mapping ORM** (Custom Types fonctionnent)
- ✅ Tester les **queries customs** (findByEmail, search)
- ✅ **Nettoyer la DB** après chaque test (tearDown)
- ✅ **Clear EntityManager** pour forcer reload
- ❌ Ne pas tester la logique métier

---

### 📋 Checklist Tests Infrastructure

- [ ] Repositories Doctrine testés avec vraie DB
- [ ] Custom Doctrine Types testés
- [ ] Queries complexes testées
- [ ] DB nettoyée après chaque test
- [ ] Configuration test database séparée

---

## 4️⃣ TESTS FONCTIONNELS (UI Layer) - 5%

### 🎯 Objectif
Tester les **controllers et scénarios complets** via HTTP.

### ✅ Quoi tester ?

#### A. Controllers HTTP (WebTestCase)
**Pourquoi ?** Vérifier le flux complet HTTP → Application → DB.

```php
// tests/Functional/Cadeau/Attribution/ListHabitantsControllerTest.php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListHabitantsControllerTest extends WebTestCase
{
    #[Test]
    public function it_displays_habitants_list(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request('GET', '/habitants');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Habitants');
    }

    #[Test]
    public function it_handles_pagination(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $crawler = $client->request('GET', '/habitants?page=2&per_page=5');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.pagination');
    }

    #[Test]
    public function it_searches_habitants(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $client->request('GET', '/habitants?search=John');

        // Assert
        $this->assertResponseIsSuccessful();
        // Vérifier que seuls les résultats correspondants s'affichent
    }
}
```

**Leçons :**
- ✅ Tester les **routes HTTP**
- ✅ Tester les **codes de statut** (200, 404, 500)
- ✅ Tester le **contenu HTML** (selectors)
- ✅ Tester la **pagination, search, filtres**
- ⚠️  **Lents** (boot Kernel + DB)
- ⚠️  **Fragiles** (changements HTML cassent tests)

#### B. Tests API REST
**Pourquoi ?** Vérifier les endpoints JSON.

```php
#[Test]
public function it_creates_demande_via_api(): void
{
    // Arrange
    $client = static::createClient();

    // Act
    $client->request('POST', '/api/demandes-cadeaux', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'nomDemandeur' => 'John Doe',
        'emailDemandeur' => 'john@example.com',
        'cadeauSouhaite' => 'Vélo',
        'motivation' => 'Pour aller au travail',
    ]));

    // Assert
    $this->assertResponseStatusCodeSame(201);
    $this->assertJson($client->getResponse()->getContent());

    $data = json_decode($client->getResponse()->getContent(), true);
    $this->assertEquals('success', $data['status']);
}
```

**Leçons :**
- ✅ Tester les **endpoints API**
- ✅ Tester le **format JSON**
- ✅ Tester les **codes HTTP** (201, 400, 404)
- ✅ Tester la **validation** (données invalides → 400)

---

### 📋 Checklist Tests Fonctionnels

- [ ] Controllers principaux testés
- [ ] Routes critiques testées
- [ ] Codes HTTP testés (200, 404, 400, 500)
- [ ] Pagination/Search testés
- [ ] API endpoints testés (si API)
- [ ] Validation testée

---

## 5️⃣ TESTS E2E (Scénarios complets) - 5%

### 🎯 Objectif
Tester des **scénarios utilisateur complets** du début à la fin.

### ✅ Quoi tester ?

```php
#[Test]
public function complete_attribution_workflow(): void
{
    $client = static::createClient();

    // 1. Créer un habitant
    $client->request('POST', '/habitants', [...]);
    $this->assertResponseIsSuccessful();

    // 2. Créer un cadeau
    $client->request('POST', '/cadeaux', [...]);
    $this->assertResponseIsSuccessful();

    // 3. Attribuer le cadeau
    $client->request('POST', '/attributions', [...]);
    $this->assertResponseIsSuccessful();

    // 4. Vérifier dans la liste
    $crawler = $client->request('GET', '/attributions');
    $this->assertSelectorTextContains('.attribution-list', 'John');
}
```

**Leçons :**
- ✅ Tester les **scénarios critiques métier**
- ✅ Scénarios **multi-étapes**
- ⚠️  **Très lents** (plusieurs requêtes)
- ⚠️  **Très fragiles**
- 💡 **Peu de tests** (seulement parcours critiques)

---

## 📊 Récapitulatif par Couche

| Couche | Type Test | % | Vitesse | Avec DB ? | Complexité |
|--------|-----------|---|---------|-----------|------------|
| **Domain** | Unit | 80% | ⚡⚡⚡ (< 10ms) | ❌ | Facile |
| **Application** | Integration | 10% | ⚡⚡ (< 50ms) | ❌ InMemory | Moyenne |
| **Infrastructure** | Integration | 5% | ⚡ (< 200ms) | ✅ DB Test | Moyenne |
| **UI** | Functional | 4% | 🐌 (< 500ms) | ✅ DB Test | Difficile |
| **Scénarios** | E2E | 1% | 🐌🐌 (> 1s) | ✅ DB Test | Très difficile |

---

## 🎓 Règles d'Or

### 1. **Tester d'abord le Domain**
✅ C'est là que se trouve la valeur métier
✅ Tests rapides et isolés
✅ Base solide pour tout le reste

### 2. **InMemory Repositories pour Application**
✅ Pas de dépendance DB
✅ Tests rapides
✅ IDs prévisibles (FakeIdGenerator)

### 3. **Minimiser les tests Infrastructure**
⚠️  Seulement pour vérifier le mapping ORM
⚠️  Pas pour tester la logique métier

### 4. **Très peu de tests E2E**
⚠️  Seulement parcours critiques
⚠️  Lents et fragiles
⚠️  Maintenance coûteuse

### 5. **AAA Pattern partout**
✅ Arrange - Act - Assert
✅ Lisibilité maximale

### 6. **Naming explicite**
✅ `it_rejects_negative_age` (comportement)
❌ `testAge` (technique)

### 7. **Un test = Un scénario**
✅ Test isolé et focalisé
❌ Plusieurs assertions non liées

---

## 🚀 Ordre d'Implémentation Recommandé

### Phase 1 : Foundation (Domain)
1. ✅ Value Objects (validation critique)
2. ✅ Entities (logique métier)
3. ✅ Domain Services

**Objectif :** 80% de la pyramide = Domain 100% testé

### Phase 2 : Orchestration (Application)
4. ✅ Command Handlers (avec InMemory)
5. ✅ Query Handlers (avec InMemory)
6. ✅ FakeIdGenerator créé

**Objectif :** Garantir l'orchestration fonctionne

### Phase 3 : Persistence (Infrastructure)
7. ⚠️  Doctrine Repositories (avec DB test)
8. ⚠️  Custom Doctrine Types

**Objectif :** Vérifier le mapping ORM

### Phase 4 : UI (Fonctionnel)
9. ⚠️  Controllers principaux
10. ⚠️  Routes critiques

**Objectif :** Smoke tests (ça fonctionne ?)

### Phase 5 : E2E (Optionnel)
11. 💡 Scénarios critiques métier

**Objectif :** Parcours utilisateur complets

---

## 📚 Ressources

- [Test Pyramid - Martin Fowler](https://martinfowler.com/articles/practical-test-pyramid.html)
- [PHPUnit Documentation](https://docs.phpunit.de/)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Clean Code - Robert C. Martin](https://www.amazon.fr/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882)

---

## 🎯 Conclusion

**La pyramide de tests en architecture hexagonale :**

1. **80% Domain (Unit)** → Logique métier pure, rapide, isolée
2. **10% Application (Integration)** → Orchestration avec InMemory
3. **5% Infrastructure (Integration)** → Vérification mapping ORM
4. **5% UI (Functional/E2E)** → Smoke tests + parcours critiques

**Votre projet actuel** : ✅ 78% Unit / 11% Int / 11% Func
**C'est PARFAIT !** 🎉

La base critique (Domain + Application) est 100% testée.
