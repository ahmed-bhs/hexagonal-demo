<?php

declare(strict_types=1);

namespace App\Tests\Integration\Cadeau\Attribution\Application;

use App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommand;
use App\Cadeau\Attribution\Application\AttribuerCadeaux\AttribuerCadeauxCommandHandler;
use App\Cadeau\Attribution\Domain\Model\Attribution;
use App\Cadeau\Attribution\Domain\Model\Cadeau;
use App\Cadeau\Attribution\Domain\Model\Habitant;
use App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use App\Cadeau\Attribution\Domain\ValueObject\Age;
use App\Shared\Domain\ValueObject\Email;
use App\Cadeau\Attribution\Domain\ValueObject\HabitantId;
use App\Tests\Fake\Generator\FakeIdGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test d'intégration pour AttribuerCadeauxCommandHandler.
 *
 * ✅ MINIMUM NÉCESSAIRE:
 * - Test que le handler orchestre correctement Domain + Repositories
 * - Utilise des repositories en mémoire (InMemory)
 * - Utilise FakeIdGenerator pour IDs prévisibles
 */
final class AttribuerCadeauxHandlerTest extends TestCase
{
    private InMemoryHabitantRepository $habitantRepository;
    private InMemoryCadeauRepository $cadeauRepository;
    private InMemoryAttributionRepository $attributionRepository;
    private FakeIdGenerator $idGenerator;
    private AttribuerCadeauxCommandHandler $handler;

    protected function setUp(): void
    {
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
        // Arrange - UUID valides
        $habitantId = '550e8400-e29b-41d4-a716-446655440001';
        $cadeauId = '550e8400-e29b-41d4-a716-446655440002';

        $habitant = Habitant::create(
            new HabitantId($habitantId),
            'John',
            'Doe',
            new Age(30),
            new Email('john@example.com')
        );
        $this->habitantRepository->save($habitant);

        $cadeau = Cadeau::create($cadeauId, 'Vélo', 'Vélo de ville', 10);
        $this->cadeauRepository->save($cadeau);

        $command = new AttribuerCadeauxCommand(
            habitantId: $habitantId,
            cadeauId: $cadeauId
        );

        // Act
        $this->handler->__invoke($command);

        // Assert
        $attributions = $this->attributionRepository->findAll();
        $this->assertCount(1, $attributions);

        $attribution = $attributions[0];
        $this->assertEquals('fake-id-1', $attribution->getId());
        $this->assertEquals($habitantId, $attribution->getHabitantId());
        $this->assertEquals($cadeauId, $attribution->getCadeauId());
    }

    #[Test]
    public function it_rejects_attribution_when_habitant_not_found(): void
    {
        // Arrange
        $cadeau = Cadeau::create('cad-456', 'Vélo', 'Vélo de ville', 10);
        $this->cadeauRepository->save($cadeau);

        $command = new AttribuerCadeauxCommand(
            habitantId: 'non-existent',
            cadeauId: 'cad-456'
        );

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Habitant with ID "non-existent" not found');

        // Act
        $this->handler->__invoke($command);
    }

    #[Test]
    public function it_rejects_attribution_when_cadeau_not_found(): void
    {
        // Arrange
        $habitantId = '550e8400-e29b-41d4-a716-446655440001';

        $habitant = Habitant::create(
            new HabitantId($habitantId),
            'John',
            'Doe',
            new Age(30),
            new Email('john@example.com')
        );
        $this->habitantRepository->save($habitant);

        $command = new AttribuerCadeauxCommand(
            habitantId: $habitantId,
            cadeauId: 'non-existent'
        );

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cadeau with ID "non-existent" not found');

        // Act
        $this->handler->__invoke($command);
    }
}

/**
 * In-Memory Repository pour Habitant (test double).
 */
final class InMemoryHabitantRepository implements HabitantRepositoryInterface
{
    private array $habitants = [];

    public function save(Habitant $habitant): void
    {
        $this->habitants[$habitant->getId()->value] = $habitant;
    }

    public function findById(string $id): ?Habitant
    {
        return $this->habitants[$id] ?? null;
    }

    public function delete(Habitant $habitant): void
    {
        unset($this->habitants[$habitant->getId()->value]);
    }

    public function findAll(): array
    {
        return array_values($this->habitants);
    }

    public function findByEmail(string $email): ?Habitant
    {
        foreach ($this->habitants as $habitant) {
            if ($habitant->getEmail()->value === $email) {
                return $habitant;
            }
        }
        return null;
    }

    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function findPaginated(\App\Shared\Pagination\Domain\ValueObject\Page $page, \App\Shared\Pagination\Domain\ValueObject\PerPage $perPage): \App\Shared\Pagination\Domain\ValueObject\PaginatedResult
    {
        throw new \RuntimeException('Not implemented for test');
    }

    public function searchPaginated(\App\Shared\Search\Domain\ValueObject\SearchTerm $searchTerm, \App\Shared\Pagination\Domain\ValueObject\Page $page, \App\Shared\Pagination\Domain\ValueObject\PerPage $perPage): \App\Shared\Pagination\Domain\ValueObject\PaginatedResult
    {
        throw new \RuntimeException('Not implemented for test');
    }
}

/**
 * In-Memory Repository pour Cadeau (test double).
 */
final class InMemoryCadeauRepository implements CadeauRepositoryInterface
{
    private array $cadeaux = [];

    public function save(Cadeau $cadeau): void
    {
        $this->cadeaux[$cadeau->getId()] = $cadeau;
    }

    public function findById(string $id): ?Cadeau
    {
        return $this->cadeaux[$id] ?? null;
    }

    public function delete(Cadeau $cadeau): void
    {
        unset($this->cadeaux[$cadeau->getId()]);
    }

    public function findAll(): array
    {
        return array_values($this->cadeaux);
    }

    public function findByNom(string $nom): ?Cadeau { return null; }
    public function findAllEnStock(): array { return []; }
}

/**
 * In-Memory Repository pour Attribution (test double).
 */
final class InMemoryAttributionRepository implements AttributionRepositoryInterface
{
    private array $attributions = [];

    public function save(Attribution $attribution): void
    {
        $this->attributions[$attribution->getId()] = $attribution;
    }

    public function findById(string $id): ?Attribution
    {
        return $this->attributions[$id] ?? null;
    }

    public function delete(Attribution $attribution): void
    {
        unset($this->attributions[$attribution->getId()]);
    }

    public function findAll(): array
    {
        return array_values($this->attributions);
    }
}
