<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine;

use App\Cadeau\Attribution\Domain\Model\Attribution;
use App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Repository Adapter (Infrastructure).
 *
 * This is an adapter in hexagonal architecture - it implements the port interface
 * and provides the actual infrastructure implementation (Doctrine ORM in this case).
 *
 * This adapter translates domain operations to infrastructure-specific operations.
 */
final class DoctrineAttributionRepository implements AttributionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Attribution $attribution): void
    {
        $this->entityManager->persist($attribution);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Attribution
    {
        return $this->entityManager->find(Attribution::class, $id);
    }

    public function delete(Attribution $attribution): void
    {
        $this->entityManager->remove($attribution);
        $this->entityManager->flush();
    }

    /**
     * @return Attribution[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Attribution::class)->findAll();
    }
}
