<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Port;

use App\Cadeau\Attribution\Domain\Model\Attribution;

/**
 * Repository Port (Interface).
 *
 * This is a port in hexagonal architecture - an interface that defines
 * the contract for persistence operations without coupling to infrastructure.
 *
 * The application layer depends on this abstraction, not on concrete implementations.
 */
interface AttributionRepositoryInterface
{
    public function save(Attribution $attribution): void;

    public function findById(string $id): ?Attribution;

    public function delete(Attribution $attribution): void;

    /**
     * @return Attribution[]
     */
    public function findAll(): array;
}
