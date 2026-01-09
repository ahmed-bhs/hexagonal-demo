<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Port;

use App\Cadeau\Attribution\Domain\Model\Habitant;
use App\Shared\Pagination\Domain\ValueObject\Page;
use App\Shared\Pagination\Domain\ValueObject\PaginatedResult;
use App\Shared\Pagination\Domain\ValueObject\PerPage;
use App\Shared\Search\Domain\ValueObject\SearchTerm;

/**
 * Repository Port (Interface).
 *
 * This is a port in hexagonal architecture - an interface that defines
 * the contract for persistence operations without coupling to infrastructure.
 *
 * The application layer depends on this abstraction, not on concrete implementations.
 */
interface HabitantRepositoryInterface
{
    public function save(Habitant $habitant): void;

    public function findById(string $id): ?Habitant;

    public function delete(Habitant $habitant): void;

    /**
     * @return Habitant[]
     */
    public function findAll(): array;

    public function findByEmail(string $email): ?Habitant;

    public function existsByEmail(string $email): bool;

    /**
     * Find habitants with pagination.
     */
    public function findPaginated(Page $page, PerPage $perPage): PaginatedResult;

    /**
     * Search habitants with pagination.
     */
    public function searchPaginated(SearchTerm $searchTerm, Page $page, PerPage $perPage): PaginatedResult;
}
