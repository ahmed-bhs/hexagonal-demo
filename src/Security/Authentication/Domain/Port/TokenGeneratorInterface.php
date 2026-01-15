<?php

declare(strict_types=1);

namespace App\Security\Authentication\Domain\Port;

use App\Security\User\Domain\Model\User;

/**
 * Token Generator Port (Interface)
 *
 * Abstracts JWT token generation.
 * Infrastructure provides JWT implementation.
 */
interface TokenGeneratorInterface
{
    /**
     * Generate JWT token for user
     *
     * @return string JWT token
     */
    public function generateToken(User $user): string;

    /**
     * Parse and validate JWT token
     *
     * @return array{userId: string, email: string, roles: array<string>}|null
     */
    public function parseToken(string $token): ?array;
}
