<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Marker interface for Domain Events.
 *
 * Domain Events represent something that happened in the domain that is
 * relevant to domain experts and other parts of the system.
 *
 * Characteristics:
 * - Immutable (use readonly classes)
 * - Past tense naming (GiftRequestSubmitted, OrderPlaced, UserRegistered)
 * - Contains only data (no behavior)
 * - Carries information about what happened, when, and relevant context
 *
 * In hexagonal architecture:
 * - Defined in Domain layer (pure PHP, no framework dependencies)
 * - Published automatically by infrastructure after aggregate persistence
 * - Can be stored in EventStore for audit/event sourcing
 */
interface DomainEvent
{
    /**
     * Get the timestamp when this event occurred.
     */
    public function occurredOn(): \DateTimeImmutable;

    /**
     * Get the aggregate ID that emitted this event.
     */
    public function aggregateId(): string;
}
