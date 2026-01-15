<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Port (interface) for storing domain events.
 *
 * EventStore is a specialized storage for domain events that provides:
 * - Append-only event log (immutable history)
 * - Audit trail (who did what, when)
 * - Event replay capability (rebuild state from events)
 * - Event sourcing support (events as source of truth)
 *
 * Use cases:
 * - Audit log: Track all business operations
 * - Debugging: Replay events to reproduce issues
 * - Analytics: Query historical events
 * - Event Sourcing: Reconstruct aggregate state from events
 * - CQRS: Build read models from events
 *
 * This port allows:
 * - Domain/Application layers to be independent of storage mechanism
 * - Infrastructure to implement with SQL, NoSQL, or specialized event stores
 * - Easy testing with InMemoryEventStore
 *
 * Hexagonal Architecture:
 * - Port: Defined in Shared/Domain (this interface)
 * - Adapter: Implemented in Infrastructure (e.g., DoctrineEventStore)
 */
interface EventStoreInterface
{
    /**
     * Append a domain event to the store.
     *
     * Events are immutable and append-only.
     * Once stored, they should never be modified or deleted.
     *
     * @param DomainEvent $event The event to store
     */
    public function append(DomainEvent $event): void;

    /**
     * Append multiple events in batch.
     *
     * @param DomainEvent[] $events
     */
    public function appendAll(array $events): void;

    /**
     * Retrieve all events for a specific aggregate.
     *
     * Useful for event sourcing: replay events to reconstruct aggregate state.
     *
     * @param string $aggregateId
     * @return DomainEvent[]
     */
    public function getEventsForAggregate(string $aggregateId): array;

    /**
     * Retrieve all events of a specific type.
     *
     * Useful for analytics and read model projections.
     *
     * @param string $eventClass Fully qualified class name
     * @return DomainEvent[]
     */
    public function getEventsByType(string $eventClass): array;

    /**
     * Retrieve all events ordered by occurrence date.
     *
     * Useful for full event replay and debugging.
     *
     * @return DomainEvent[]
     */
    public function getAllEvents(): array;
}
