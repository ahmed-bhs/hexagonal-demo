<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stored Event Entity for EventStore.
 *
 * This entity represents a persisted domain event in the event store.
 * It's an infrastructure concern - Domain events remain pure PHP objects.
 *
 * Schema Design:
 * - id: Auto-increment primary key (for ordering and pagination)
 * - eventId: UUID of the event (unique identifier)
 * - eventType: FQCN of the event class (e.g., GiftRequestSubmitted)
 * - aggregateId: ID of the aggregate that emitted the event
 * - payload: JSON-serialized event data
 * - occurredOn: When the event occurred (from domain event)
 * - recordedAt: When the event was stored (infrastructure timestamp)
 *
 * Use cases:
 * - Audit trail: Query events by aggregate, type, or date
 * - Event replay: Reconstruct aggregate state
 * - Analytics: Query historical business events
 * - Debugging: Trace application behavior
 *
 * Note: This is NOT a Domain entity - it's pure Infrastructure.
 * Domain events are serialized to JSON for storage.
 */
#[ORM\Entity]
#[ORM\Table(name: 'event_store')]
#[ORM\Index(columns: ['aggregate_id'], name: 'idx_aggregate_id')]
#[ORM\Index(columns: ['event_type'], name: 'idx_event_type')]
#[ORM\Index(columns: ['occurred_on'], name: 'idx_occurred_on')]
class StoredEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $eventId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $eventType;

    #[ORM\Column(type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredOn;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    public function __construct(
        string $eventId,
        string $eventType,
        string $aggregateId,
        array $payload,
        \DateTimeImmutable $occurredOn
    ) {
        $this->eventId = $eventId;
        $this->eventType = $eventType;
        $this->aggregateId = $aggregateId;
        $this->payload = $payload;
        $this->occurredOn = $occurredOn;
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
