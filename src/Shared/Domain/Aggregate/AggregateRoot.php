<?php

declare(strict_types=1);

namespace App\Shared\Domain\Aggregate;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Trait for Aggregate Roots to collect Domain Events.
 *
 * In DDD, an Aggregate Root is the entry point to an aggregate - a cluster of
 * domain objects that should be treated as a single unit for data changes.
 *
 * This trait provides the mechanism to:
 * 1. Record domain events when business operations occur
 * 2. Retrieve recorded events for publication (after successful persistence)
 * 3. Clear events after publication
 *
 * Pattern: Collect domain events, publish after DB commit (transaction-safe)
 *
 * Usage:
 * ```php
 * class Order {
 *     use AggregateRoot;
 *
 *     public static function place(...): self {
 *         $order = new self(...);
 *         $order->recordThat(new OrderPlaced(...));
 *         return $order;
 *     }
 * }
 * ```
 *
 * Infrastructure responsibility:
 * - Doctrine Listener calls pullDomainEvents() after flush
 * - Events are published only if transaction succeeds
 * - Can be stored in EventStore for audit/replay
 */
trait AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /**
     * Record a domain event.
     *
     * Events are stored in memory until pulled by infrastructure.
     * This keeps the domain layer pure - no immediate side effects.
     */
    protected function recordThat(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Pull (retrieve and clear) all recorded domain events.
     *
     * This method is called by infrastructure (Doctrine Listener) after
     * successful flush to retrieve events for publication.
     *
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * Check if aggregate has recorded events.
     */
    public function hasDomainEvents(): bool
    {
        return !empty($this->domainEvents);
    }
}
