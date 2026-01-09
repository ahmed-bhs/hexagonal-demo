<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Port;

use App\Cadeau\Attribution\Domain\Model\Cadeau;

/**
 * Repository Port (Interface).
 *
 * This is a port in hexagonal architecture - an interface that defines
 * the contract for persistence operations without coupling to infrastructure.
 *
 * The application layer depends on this abstraction, not on concrete implementations.
 */
interface CadeauRepositoryInterface
{
    public function save(Cadeau $cadeau): void;

    public function findById(string $id): ?Cadeau;

    public function delete(Cadeau $cadeau): void;

    /**
     * @return Cadeau[]
     */
    public function findAll(): array;

    public function findByNom(string $nom): ?Cadeau;

    /**
     * @return Cadeau[]
     */
    public function findAllEnStock(): array;
}
