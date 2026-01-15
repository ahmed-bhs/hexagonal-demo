<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Query\RecupererHabitants;

use App\Cadeau\Attribution\Domain\Model\Habitant;

/**
 * Query Response.
 *
 * Contains the data returned by a query.
 * Should be immutable and contain only the data needed by the client.
 */
final readonly class RecupererHabitantsResponse
{
    /**
     * @param Habitant[] $habitants
     */
    public function __construct(
        public array $habitants,
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $totalPages,
        public bool $hasNextPage,
        public bool $hasPreviousPage,
    ) {
    }

    /**
     * @return array<int, array{id: string, prenom: string, nom: string, age: int, email: string}>
     */
    public function toArray(): array
    {
        return array_map(
            fn(Habitant $habitant) => [
                'id' => $habitant->getId()->toString(),
                'prenom' => $habitant->getPrenom(),
                'nom' => $habitant->getNom(),
                'age' => $habitant->getAge()->value,
                'email' => $habitant->getEmail()->value,
            ],
            $this->habitants
        );
    }
}
