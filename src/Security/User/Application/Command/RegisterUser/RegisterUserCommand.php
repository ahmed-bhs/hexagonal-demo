<?php

declare(strict_types=1);

namespace App\Security\User\Application\Command\RegisterUser;

/**
 * Command: Register User
 *
 * Pure DTO with registration data.
 */
final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
        public array $roles = ['ROLE_USER'],
    ) {}
}
