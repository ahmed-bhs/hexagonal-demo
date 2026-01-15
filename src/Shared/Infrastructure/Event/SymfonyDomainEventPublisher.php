<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Port\DomainEventPublisherInterface;
use App\Shared\Domain\Port\EventStoreInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

/**
 * Adapter that publishes Domain Events via Symfony EventDispatcher + EventStore.
 *
 * Hexagonal Architecture:
 * - PORT: DomainEventPublisherInterface (defined in Shared/Domain)
 * - ADAPTER: This class (implemented in Shared/Infrastructure)
 *
 * Responsibilities:
 * 1. Store events in EventStore (audit trail, event sourcing)
 * 2. Publish to Symfony EventDispatcher (side effects: email, notifications)
 * 3. Handle errors gracefully (log but don't fail the transaction)
 *
 * Flow:
 * 1. DomainEventPublisherListener (Doctrine) pulls events from aggregates
 * 2. Calls this adapter's publish() or publishAll()
 * 3. This adapter:
 *    a) Stores in EventStore (append-only log)
 *    b) Dispatches to Symfony EventDispatcher
 * 4. Symfony EventSubscribers react (email, logs, notifications)
 *
 * Benefits:
 * ✅ Automatic EventStore persistence (complete audit trail)
 * ✅ Domain layer remains framework-agnostic
 * ✅ EventStore is independent of subscribers (can't be skipped)
 * ✅ Testable with FakeDomainEventPublisher and InMemoryEventStore
 *
 * Error handling:
 * - EventStore errors are logged but don't prevent event publishing
 * - This ensures side effects (email) still work even if EventStore fails
 * - In production, you might want stricter error handling
 */
final readonly class SymfonyDomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(
        private SymfonyEventDispatcherInterface $symfonyEventDispatcher,
        private EventStoreInterface $eventStore,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Publish a single domain event.
     *
     * Steps:
     * 1. Store in EventStore (audit trail)
     * 2. Dispatch to Symfony EventDispatcher (side effects)
     */
    public function publish(DomainEvent $event): void
    {
        // Step 1: Store in EventStore (audit trail, event sourcing)
        try {
            $this->eventStore->append($event);
        } catch (\Throwable $e) {
            // Log error but continue - don't prevent event publishing
            $this->logger->error('Failed to store event in EventStore', [
                'event' => get_class($event),
                'aggregateId' => $event->aggregateId(),
                'error' => $e->getMessage(),
            ]);
        }

        // Step 2: Dispatch to Symfony EventDispatcher (side effects: email, etc.)
        try {
            $this->symfonyEventDispatcher->dispatch($event);
        } catch (\Throwable $e) {
            // Log error - subscribers should handle their own errors
            $this->logger->error('Error while dispatching domain event', [
                'event' => get_class($event),
                'aggregateId' => $event->aggregateId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Publish multiple domain events in batch.
     *
     * Events are published in the order they were recorded by the aggregate.
     * This maintains event causality and ordering.
     *
     * Uses batch append for EventStore efficiency.
     */
    public function publishAll(array $events): void
    {
        if (empty($events)) {
            return;
        }

        // Step 1: Batch store in EventStore for efficiency
        try {
            $this->eventStore->appendAll($events);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to store events in EventStore', [
                'eventCount' => count($events),
                'error' => $e->getMessage(),
            ]);
        }

        // Step 2: Dispatch each event to Symfony EventDispatcher
        foreach ($events as $event) {
            try {
                $this->symfonyEventDispatcher->dispatch($event);
            } catch (\Throwable $e) {
                $this->logger->error('Error while dispatching domain event', [
                    'event' => get_class($event),
                    'aggregateId' => $event->aggregateId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
