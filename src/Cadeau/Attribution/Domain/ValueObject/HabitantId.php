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
 *
 * ✅ HEXAGONAL: Ce ValueObject ne génère PAS d'UUID lui-même.
 * L'UUID est généré dans la couche Application/Infrastructure et passé ici.
 * Cela garde le Domain pur sans dépendance externe.
 */
final readonly class HabitantId
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(
        public string $value,
    ) {
        // Validation du format UUID (sans dépendance externe)
        if (!preg_match(self::UUID_PATTERN, $value)) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID format: "%s"', $value));
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
