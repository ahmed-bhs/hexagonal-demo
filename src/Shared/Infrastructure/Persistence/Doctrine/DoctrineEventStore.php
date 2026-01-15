<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Port\EventStoreInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\StoredEvent;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine implementation of EventStore.
 *
 * This adapter persists domain events to a database table for:
 * - Audit trail (complete history of business operations)
 * - Event replay (reconstruct aggregate state)
 * - Analytics (query historical events)
 * - Debugging (trace application behavior)
 *
 * Architecture:
 * - PORT: EventStoreInterface (Shared/Domain)
 * - ADAPTER: This class (Shared/Infrastructure)
 *
 * Event serialization:
 * - Domain events are serialized to JSON
 * - Event type (FQCN) is stored for deserialization
 * - Payload contains all event properties
 *
 * Performance considerations:
 * - Events are append-only (no updates/deletes)
 * - Indexes on aggregate_id, event_type, occurred_on
 * - Batch operations supported for efficiency
 *
 * Note: This implementation uses simple JSON serialization.
 * For production, consider:
 * - Event versioning (handle schema evolution)
 * - Event encryption (sensitive data)
 * - Partitioning (large event volumes)
 * - Specialized event stores (EventStoreDB, Axon, Prooph)
 */
final readonly class DoctrineEventStore implements EventStoreInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Append a single domain event to the store.
     */
    public function append(DomainEvent $event): void
    {
        $storedEvent = $this->serializeEvent($event);

        $this->entityManager->persist($storedEvent);
        $this->entityManager->flush();
    }

    /**
     * Append multiple events in batch for efficiency.
     */
    public function appendAll(array $events): void
    {
        if (empty($events)) {
            return;
        }

        foreach ($events as $event) {
            $storedEvent = $this->serializeEvent($event);
            $this->entityManager->persist($storedEvent);
        }

        $this->entityManager->flush();
    }

    /**
     * Retrieve all events for a specific aggregate.
     */
    public function getEventsForAggregate(string $aggregateId): array
    {
        $storedEvents = $this->entityManager
            ->getRepository(StoredEvent::class)
            ->findBy(['aggregateId' => $aggregateId], ['occurredOn' => 'ASC']);

        return array_map(
            fn(StoredEvent $stored) => $this->deserializeEvent($stored),
            $storedEvents
        );
    }

    /**
     * Retrieve all events of a specific type.
     */
    public function getEventsByType(string $eventClass): array
    {
        $storedEvents = $this->entityManager
            ->getRepository(StoredEvent::class)
            ->findBy(['eventType' => $eventClass], ['occurredOn' => 'ASC']);

        return array_map(
            fn(StoredEvent $stored) => $this->deserializeEvent($stored),
            $storedEvents
        );
    }

    /**
     * Retrieve all events ordered by occurrence.
     */
    public function getAllEvents(): array
    {
        $storedEvents = $this->entityManager
            ->getRepository(StoredEvent::class)
            ->findBy([], ['occurredOn' => 'ASC']);

        return array_map(
            fn(StoredEvent $stored) => $this->deserializeEvent($stored),
            $storedEvents
        );
    }

    /**
     * Serialize domain event to StoredEvent entity.
     */
    private function serializeEvent(DomainEvent $event): StoredEvent
    {
        // Generate unique event ID
        $eventId = $this->generateEventId();

        // Serialize event to array (simple reflection-based approach)
        $payload = $this->eventToArray($event);

        return new StoredEvent(
            eventId: $eventId,
            eventType: get_class($event),
            aggregateId: $event->aggregateId(),
            payload: $payload,
            occurredOn: $event->occurredOn()
        );
    }

    /**
     * Deserialize StoredEvent back to domain event.
     *
     * Note: This is a simple implementation. For production, consider:
     * - Event versioning and migration
     * - Using serializer library (Symfony Serializer, JMS)
     */
    private function deserializeEvent(StoredEvent $storedEvent): DomainEvent
    {
        $eventClass = $storedEvent->getEventType();
        $payload = $storedEvent->getPayload();

        // Reconstruct event from payload
        // This assumes events have constructor with named parameters matching payload
        return new $eventClass(...$payload);
    }

    /**
     * Convert event object to array using reflection.
     */
    private function eventToArray(DomainEvent $event): array
    {
        $reflection = new \ReflectionClass($event);
        $properties = $reflection->getProperties();

        $payload = [];
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($event);

            // Handle DateTimeImmutable serialization
            if ($value instanceof \DateTimeImmutable) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }

            $payload[$property->getName()] = $value;
        }

        return $payload;
    }

    /**
     * Generate unique event ID (UUID v7).
     */
    private function generateEventId(): string
    {
        return \Symfony\Component\Uid\Uuid::v7()->toRfc4122();
    }
}
