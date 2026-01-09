<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine;

use App\Cadeau\Attribution\Domain\Model\Cadeau;
use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Repository Adapter (Infrastructure).
 *
 * This is an adapter in hexagonal architecture - it implements the port interface
 * and provides the actual infrastructure implementation (Doctrine ORM in this case).
 *
 * This adapter translates domain operations to infrastructure-specific operations.
 */
final class DoctrineCadeauRepository implements CadeauRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Cadeau $cadeau): void
    {
        $this->entityManager->persist($cadeau);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Cadeau
    {
        return $this->entityManager->find(Cadeau::class, $id);
    }

    public function delete(Cadeau $cadeau): void
    {
        $this->entityManager->remove($cadeau);
        $this->entityManager->flush();
    }

    /**
     * @return Cadeau[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Cadeau::class)->findAll();
    }

    public function findByNom(string $nom): ?Cadeau
    {
        return $this->entityManager->getRepository(Cadeau::class)->findOneBy(['nom' => $nom]);
    }

    /**
     * @return Cadeau[]
     */
    public function findAllEnStock(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Cadeau::class, 'c')
            ->where('c.quantite > 0')
            ->getQuery()
            ->getResult();
    }
}
