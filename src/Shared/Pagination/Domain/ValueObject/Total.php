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
final readonly class Total
{
    public function __construct(
        public int $value,
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Total must be greater than or equal to 0');
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

    public function isEmpty(): bool
    {
        return $this->value === 0;
    }
}
