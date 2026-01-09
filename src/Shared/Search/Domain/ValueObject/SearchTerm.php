<?php

declare(strict_types=1);

namespace App\Shared\Search\Domain\ValueObject;

/**
 * Domain Value Object.
 *
 * Represents a domain concept defined by its attributes rather than identity.
 * Value objects are immutable and can be compared by value.
 *
 * In hexagonal architecture, value objects are part of the Domain layer
 * and help enforce domain invariants and encapsulate business rules.
 */
final readonly class SearchTerm
{
    public function __construct(
        public string $value,
    ) {
        $trimmed = trim($value);
        if (strlen($trimmed) > 0 && strlen($trimmed) < 2) {
            throw new \InvalidArgumentException('Search term must be at least 2 characters long');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return trim($this->value) === '';
    }
}
