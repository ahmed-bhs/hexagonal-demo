<?php

declare(strict_types=1);

namespace App\Security\User\Infrastructure\Security;

use App\Security\User\Domain\Port\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Symfony Password Hasher (Adapter)
 *
 * Implements PasswordHasherInterface port using Symfony's password hasher.
 * Uses algorithm configured in security.yaml (bcrypt, argon2, etc.)
 */
final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
    ) {}

    public function hash(string $plainPassword): string
    {
        $hasher = $this->hasherFactory->getPasswordHasher('common');

        return $hasher->hash($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        $hasher = $this->hasherFactory->getPasswordHasher('common');

        return $hasher->verify($hashedPassword, $plainPassword);
    }
}
