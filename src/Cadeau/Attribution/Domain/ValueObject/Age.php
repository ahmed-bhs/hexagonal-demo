<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\ValueObject;

/**
 * Domain Value Object.
 *
 * Represents a domain concept defined by its attributes rather than identity.
 * Value objects are immutable and can be compared by value.
 *
 * In hexagonal architecture, value objects are part of the Domain layer
 * and help enforce domain invariants and encapsulate business rules.
 */
final readonly class Age
{
    public function __construct(
        public int $value,
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Age cannot be negative');
        }

        if ($value > 150) {
            throw new \InvalidArgumentException('Age cannot exceed 150 years');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isAdult(): bool
    {
        return $this->value >= 18;
    }

    public function isSenior(): bool
    {
        return $this->value >= 65;
    }

    public function isChild(): bool
    {
        return $this->value < 18;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
