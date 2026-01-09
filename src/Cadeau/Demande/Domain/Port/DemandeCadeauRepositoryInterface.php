<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Domain\Port;

use App\Cadeau\Demande\Domain\Model\DemandeCadeau;

/**
 * Repository Port (Interface).
 *
 * This is a port in hexagonal architecture - an interface that defines
 * the contract for persistence operations without coupling to infrastructure.
 *
 * The application layer depends on this abstraction, not on concrete implementations.
 */
interface DemandeCadeauRepositoryInterface
{
    /**
     * Persist an entity to the storage.
     */
    public function save(DemandeCadeau $demandecadeau): void;

    /**
     * Find an entity by its identifier.
     */
    public function find(string $id): ?DemandeCadeau;

    /**
     * Retrieve all entities.
     *
     * @return DemandeCadeau[]
     */
    public function findAll(): array;
}
