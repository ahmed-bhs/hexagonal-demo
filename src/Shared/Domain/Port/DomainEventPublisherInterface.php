<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Port (interface) for publishing domain events.
 *
 * This interface is defined in the Domain layer and allows Infrastructure
 * to publish events without Domain knowing the implementation details.
 *
 * DDD Pattern: Domain Event Publisher
 * - Domain layer defines the contract (this port)
 * - Infrastructure implements the adapter (e.g., SymfonyDomainEventPublisher)
 * - Events are published AFTER successful aggregate persistence
 *
 * Implementation strategies:
 * 1. Synchronous: Publish immediately via Symfony EventDispatcher
 * 2. Asynchronous: Publish to message queue (Symfony Messenger)
 * 3. Hybrid: Store in EventStore + publish
 *
 * Hexagonal Architecture:
 * - Port: Defined in Shared/Domain (this interface)
 * - Adapter: Implemented in Infrastructure
 *
 * Usage by Infrastructure:
 * - Doctrine Listener pulls events from aggregates after flush
 * - Calls publish() for each event
 * - Events reach EventSubscribers in Infrastructure layer
 */
interface DomainEventPublisherInterface
{
    /**
     * Publish a domain event.
     *
     * Events are published to subscribers (e.g., email sender, notification service).
     * This method is called by infrastructure after successful DB commit.
     *
     * @param DomainEvent $event The domain event to publish
     */
    public function publish(DomainEvent $event): void;

    /**
     * Publish multiple domain events.
     *
     * Batch publishing for efficiency when multiple events are pulled
     * from aggregates after flush.
     *
     * @param DomainEvent[] $events
     */
    public function publishAll(array $events): void;
}
