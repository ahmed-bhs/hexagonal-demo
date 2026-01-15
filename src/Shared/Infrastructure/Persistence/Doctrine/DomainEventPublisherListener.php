<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\Port\DomainEventPublisherInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Doctrine Listener that automatically publishes Domain Events after flush.
 *
 * DDD Pattern: Automatic Event Publishing
 * This is the bridge between Domain aggregates and Infrastructure event system.
 *
 * How it works:
 * 1. CommandHandler creates/modifies aggregate
 * 2. Aggregate records domain events (via AggregateRoot trait)
 * 3. Repository calls flush()
 * 4. THIS LISTENER is triggered after successful flush
 * 5. Pulls domain events from all aggregates
 * 6. Publishes events via DomainEventPublisherInterface
 * 7. EventSubscribers react (send email, log, store in EventStore, etc.)
 *
 * Benefits:
 * ✅ 100% Transaction-safe: Events published ONLY if DB commit succeeds
 * ✅ No manual event dispatch in CommandHandlers
 * ✅ Domain aggregates remain pure (no infrastructure dependency)
 * ✅ Centralized event publishing logic
 * ✅ Impossible to forget to publish events
 *
 * Architecture:
 * - Shared/Infrastructure (reusable across bounded contexts)
 * - Depends on: DomainEventPublisherInterface (port)
 * - Works with: Any aggregate using AggregateRoot trait
 *
 * Alternative approaches:
 * - onFlush: Publish during flush (risky if flush fails)
 * - postPersist/postUpdate: Per-entity (doesn't guarantee transaction success)
 * - postFlush: ✅ Current approach (safest)
 */
#[AsDoctrineListener(event: Events::postFlush)]
final readonly class DomainEventPublisherListener
{
    public function __construct(
        private DomainEventPublisherInterface $eventPublisher
    ) {
    }

    /**
     * Called after Doctrine flush completes successfully.
     *
     * At this point:
     * - All entities are persisted to database
     * - Transaction is committed
     * - Safe to publish events and trigger side effects
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // Collect all domain events from managed aggregates
        $domainEvents = [];

        // Check all entities in identity map (managed entities)
        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                // Only process aggregates that use AggregateRoot trait
                if ($this->isAggregateRoot($entity)) {
                    /** @var AggregateRoot $entity */
                    if ($entity->hasDomainEvents()) {
                        $events = $entity->pullDomainEvents();
                        $domainEvents = array_merge($domainEvents, $events);
                    }
                }
            }
        }

        // Publish all collected events
        if (!empty($domainEvents)) {
            $this->eventPublisher->publishAll($domainEvents);
        }
    }

    /**
     * Check if entity uses AggregateRoot trait (has domain event methods).
     */
    private function isAggregateRoot(object $entity): bool
    {
        return method_exists($entity, 'pullDomainEvents')
            && method_exists($entity, 'hasDomainEvents');
    }
}
