<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\DTO;

/**
 * Token DTO
 *
 * Returned after successful login.
 */
final readonly class TokenDTO
{
    public function __construct(
        public string $token,
        public string $userId,
        public string $email,
        public array $roles,
    ) {}

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'user' => [
                'id' => $this->userId,
                'email' => $this->email,
                'roles' => $this->roles,
            ],
        ];
    }
}
