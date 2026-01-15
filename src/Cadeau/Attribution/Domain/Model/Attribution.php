<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Model;

use App\Cadeau\Attribution\Domain\Event\GiftAttributed;
use App\Shared\Domain\Aggregate\AggregateRoot;

/**
 * Aggregate Root: Gift Attribution.
 *
 * Represents the attribution of a gift to a resident.
 * This is an aggregate root that records domain events.
 *
 * Domain Events:
 * - GiftAttributed: When a gift is successfully attributed to a resident
 *
 * In hexagonal architecture, entities are part of the Domain layer (core)
 * and are completely independent of infrastructure concerns.
 *
 * ⚠️ IMPORTANT: This entity is PURE - no framework dependencies.
 * Doctrine ORM mapping is configured separately in XML.
 */
class Attribution
{
    use AggregateRoot;
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

    public static function createWithDetails(
        string $id,
        string $habitantId,
        string $habitantName,
        string $habitantEmail,
        string $cadeauId,
        string $cadeauName
    ): self {
        $attribution = new self(
            $id,
            $habitantId,
            $cadeauId,
            new \DateTimeImmutable()
        );

        // Record domain event - will be published by infrastructure after successful flush
        $attribution->recordThat(new GiftAttributed(
            attributionId: $attribution->id,
            habitantId: $attribution->habitantId,
            habitantName: $habitantName,
            habitantEmail: $habitantEmail,
            giftId: $attribution->cadeauId,
            giftName: $cadeauName,
            attributedAt: $attribution->dateAttribution
        ));

        return $attribution;
    }

    /**
     * @deprecated Use createWithDetails() instead to ensure domain event has full context
     */
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
