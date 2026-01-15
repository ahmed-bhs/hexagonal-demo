<?php

declare(strict_types=1);

namespace App\Security\User\Application\DTO;

use App\Security\User\Domain\Model\User;

/**
 * User DTO
 *
 * Data transfer object for user information.
 * No sensitive data (password).
 */
final readonly class UserDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public array $roles,
        public string $createdAt,
        public ?string $lastLoginAt,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            email: $user->email()->value(),
            roles: $user->roles(),
            createdAt: $user->createdAt()->format('c'),
            lastLoginAt: $user->lastLoginAt()?->format('c'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
            'createdAt' => $this->createdAt,
            'lastLoginAt' => $this->lastLoginAt,
        ];
    }
}
