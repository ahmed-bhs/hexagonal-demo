<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Query\RecupererStatistiques;

/**
 * Query - Read Operation.
 *
 * Represents a request to retrieve statistics data.
 * Queries are read-only operations in CQRS pattern.
 *
 * In hexagonal architecture, this is part of the Application layer
 * and represents a use case for reading data.
 */
final readonly class RecupererStatistiquesQuery
{
    public function __construct()
    {
    }
}
