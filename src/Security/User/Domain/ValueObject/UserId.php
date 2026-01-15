<?php

declare(strict_types=1);

namespace App\Security\User\Domain\ValueObject;

/**
 * User ID Value Object
 */
final readonly class UserId
{
    public function __construct(
        private string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
