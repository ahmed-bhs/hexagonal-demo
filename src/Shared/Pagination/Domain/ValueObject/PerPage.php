<?php

declare(strict_types=1);

namespace App\Shared\Pagination\Domain\ValueObject;

/**
 * Domain Value Object.
 *
 * Represents a domain concept defined by its attributes rather than identity.
 * Value objects are immutable and can be compared by value.
 *
 * In hexagonal architecture, value objects are part of the Domain layer
 * and help enforce domain invariants and encapsulate business rules.
 */
final readonly class PerPage
{
    public function __construct(
        public int $value,
    ) {
        if ($value < 1 || $value > 100) {
            throw new \InvalidArgumentException('Items per page must be between 1 and 100');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
