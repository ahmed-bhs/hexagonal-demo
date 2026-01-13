<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\Persistence\Doctrine;

use App\Cadeau\Attribution\Domain\Model\Habitant;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use App\Shared\Pagination\Domain\ValueObject\Page;
use App\Shared\Pagination\Domain\ValueObject\PaginatedResult;
use App\Shared\Pagination\Domain\ValueObject\PerPage;
use App\Shared\Pagination\Domain\ValueObject\Total;
use App\Shared\Search\Domain\ValueObject\SearchTerm;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Repository Adapter (Infrastructure).
 *
 * This is an adapter in hexagonal architecture - it implements the port interface
 * and provides the actual infrastructure implementation (Doctrine ORM in this case).
 *
 * This adapter translates domain operations to infrastructure-specific operations.
 */
final class DoctrineHabitantRepository implements HabitantRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Habitant $habitant): void
    {
        $this->entityManager->persist($habitant);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Habitant
    {
        return $this->entityManager->find(Habitant::class, $id);
    }

    public function delete(Habitant $habitant): void
    {
        $this->entityManager->remove($habitant);
        $this->entityManager->flush();
    }

    /**
     * @return Habitant[]
     */
    public function findAll(): array
    {
        return $this->entityManager->getRepository(Habitant::class)->findAll();
    }

    public function findByEmail(string $email): ?Habitant
    {
        return $this->entityManager->createQueryBuilder()
            ->select('h')
            ->from(Habitant::class, 'h')
            ->where('h.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function findPaginated(Page $page, PerPage $perPage): PaginatedResult
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('h')
            ->from(Habitant::class, 'h')
            ->orderBy('h.nom', 'ASC');

        return $this->paginate($qb, $page, $perPage);
    }

    public function searchPaginated(SearchTerm $searchTerm, Page $page, PerPage $perPage): PaginatedResult
    {
        if ($searchTerm->isEmpty()) {
            return $this->findPaginated($page, $perPage);
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('h')
            ->from(Habitant::class, 'h')
            ->where('h.nom LIKE :search OR h.prenom LIKE :search OR h.email LIKE :search')
            ->setParameter('search', '%' . $searchTerm->value . '%')
            ->orderBy('h.nom', 'ASC');

        return $this->paginate($qb, $page, $perPage);
    }

    private function paginate(QueryBuilder $qb, Page $page, PerPage $perPage): PaginatedResult
    {
        $qb->setFirstResult(($page->toInt() - 1) * $perPage->toInt())
            ->setMaxResults($perPage->toInt());

        $paginator = new Paginator($qb->getQuery());
        $total = new Total(count($paginator));

        return new PaginatedResult(
            items: iterator_to_array($paginator),
            currentPage: $page,
            perPage: $perPage,
            total: $total
        );
    }
}
