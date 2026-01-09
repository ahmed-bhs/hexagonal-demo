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
final readonly class Page
{
    public function __construct(
        public int $value,
    ) {
        if ($value < 1) {
            throw new \InvalidArgumentException('Page number must be greater than 0');
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

    public function next(): self
    {
        return new self($this->value + 1);
    }

    public function previous(): self
    {
        return new self(max(1, $this->value - 1));
    }
}
