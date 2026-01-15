<?php

declare(strict_types=1);

namespace App\Security\User\Domain\Port;

/**
 * Password Hasher Port (Interface)
 *
 * Abstracts password hashing mechanism.
 * Infrastructure can use bcrypt, argon2, etc.
 */
interface PasswordHasherInterface
{
    /**
     * Hash plain password
     */
    public function hash(string $plainPassword): string;

    /**
     * Verify plain password against hash
     */
    public function verify(string $hashedPassword, string $plainPassword): bool;
}
