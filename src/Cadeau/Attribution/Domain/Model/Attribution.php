<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Model;

/**
 * Domain Entity.
 *
 * Represents a domain concept with identity and lifecycle.
 * Contains business logic and enforces invariants.
 *
 * In hexagonal architecture, entities are part of the Domain layer (core)
 * and are completely independent of infrastructure concerns.
 *
 * ⚠️ IMPORTANT: This entity is PURE - no framework dependencies.
 * Doctrine ORM mapping is configured separately in:
 * Infrastructure/Persistence/Doctrine/Orm/Mapping/Attribution.orm.yml
 */
class Attribution
{
    private string $id;
    private string $habitantId;
    private string $cadeauId;
    private \DateTimeImmutable $dateAttribution;

    public function __construct(
        string $id,
        string $habitantId,
        string $cadeauId,
        \DateTimeImmutable $dateAttribution,
    ) {
        $this->id = $id;

        // Domain validation
        if (empty(trim($habitantId))) {
            throw new \InvalidArgumentException('habitantId cannot be empty');
        }

        if (empty(trim($cadeauId))) {
            throw new \InvalidArgumentException('cadeauId cannot be empty');
        }
        // Initialize properties
        $this->habitantId = trim($habitantId);
        $this->cadeauId = trim($cadeauId);
        $this->dateAttribution = $dateAttribution;
    }

    public static function create(
        string $id,
        string $habitantId,
        string $cadeauId
    ): self {
        return new self(
            $id,
            $habitantId,
            $cadeauId,
            new \DateTimeImmutable()
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    // Getters

    public function getHabitantId(): string
    {
        return $this->habitantId;
    }

    public function getCadeauId(): string
    {
        return $this->cadeauId;
    }

    public function getDateAttribution(): \DateTimeImmutable
    {
        return $this->dateAttribution;
    }

    // Business logic methods
    // Add your domain-specific methods here
}
