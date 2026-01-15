<?php

declare(strict_types=1);

namespace App\Security\User\Domain\ValueObject;

/**
 * Hashed Password Value Object
 *
 * Represents an already-hashed password.
 * Never stores plain passwords.
 */
final readonly class HashedPassword
{
    public function __construct(
        private string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Password hash cannot be empty');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
