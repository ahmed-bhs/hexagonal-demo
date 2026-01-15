<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Query\GetCurrentUser;

/**
 * Query: Get Current User
 *
 * Returns currently authenticated user info.
 */
final readonly class GetCurrentUserQuery
{
    public function __construct(
        public string $userId
    ) {}
}
