<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Domain Value Object.
 *
 * Represents a domain concept defined by its attributes rather than identity.
 * Value objects are immutable and can be compared by value.
 *
 * In hexagonal architecture, value objects are part of the Domain layer
 * and help enforce domain invariants and encapsulate business rules.
 */
final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Email cannot be empty');
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('Invalid email format: "%s"', $value));
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
