<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Infrastructure\Persistence\Doctrine;

use App\Cadeau\Demande\Domain\Model\DemandeCadeau;
use App\Cadeau\Demande\Domain\Port\DemandeCadeauRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Repository Adapter (Infrastructure).
 *
 * This is an adapter in hexagonal architecture - it implements the port interface
 * and provides the actual infrastructure implementation (Doctrine ORM in this case).
 *
 * This adapter translates domain operations to infrastructure-specific operations.
 */
final class DoctrineDemandeCadeauRepository implements DemandeCadeauRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(DemandeCadeau $demandecadeau): void
    {
        $this->entityManager->persist($demandecadeau);
        $this->entityManager->flush();
    }

    public function find(string $id): ?DemandeCadeau
    {
        return $this->entityManager->find(DemandeCadeau::class, $id);
    }

    /**
     * @return DemandeCadeau[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(DemandeCadeau::class)->findAll();
    }
}
