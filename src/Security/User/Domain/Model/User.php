<?php

declare(strict_types=1);

namespace App\Security\User\Domain\Model;

use App\Security\User\Domain\Event\UserRegistered;
use App\Security\User\Domain\ValueObject\Email;
use App\Security\User\Domain\ValueObject\HashedPassword;
use App\Security\User\Domain\ValueObject\UserId;
use App\Shared\Domain\Aggregate\AggregateRoot;

/**
 * User Aggregate Root
 *
 * Represents a user in the authentication system.
 * This is a pure Domain entity with no framework dependencies.
 */
final class User
{
    use AggregateRoot;

    private function __construct(
        private UserId $id,
        private Email $email,
        private HashedPassword $password,
        private array $roles,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $lastLoginAt = null,
    ) {}

    /**
     * Create new user (registration)
     */
    public static function register(
        UserId $id,
        Email $email,
        HashedPassword $password,
        array $roles = ['ROLE_USER']
    ): self {
        $user = new self(
            id: $id,
            email: $email,
            password: $password,
            roles: $roles,
            createdAt: new \DateTimeImmutable(),
        );

        // Record domain event
        $user->recordThat(new UserRegistered(
            userId: $id->value(),
            email: $email->value(),
            occurredAt: new \DateTimeImmutable(),
        ));

        return $user;
    }

    /**
     * Record successful login
     */
    public function recordLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
    }

    /**
     * Check if password matches
     */
    public function verifyPassword(string $plainPassword, PasswordHasherInterface $hasher): bool
    {
        return $hasher->verify($this->password->value(), $plainPassword);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    // Getters
    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function roles(): array
    {
        return $this->roles;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }
}
