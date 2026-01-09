<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\RecupererHabitants;

/**
 * CQRS Query.
 *
 * Represents an intention to retrieve data (read operation).
 * Queries should be immutable and contain all parameters needed to fetch the data.
 */
final readonly class RecupererHabitantsQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 10,
        public string $searchTerm = '',
    ) {
    }
}
