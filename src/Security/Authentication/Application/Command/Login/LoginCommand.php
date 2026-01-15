<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Command\Login;

/**
 * Command: Login
 *
 * Pure DTO with login credentials.
 */
final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
    ) {}
}
