<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Domain Event: Gift has been attributed to a resident.
 *
 * This event is raised when a gift is successfully attributed to a resident (habitant).
 * It represents an important business operation that triggers several side effects:
 *
 * Side effects (handled by subscribers):
 * - Send confirmation email to resident
 * - Generate gift certificate PDF (ASYNC via Messenger)
 * - Notify administrator
 * - Update stock count
 * - Store in EventStore for audit
 *
 * Event naming:
 * - Past tense (GiftAttributed, not AttributeGift)
 * - Describes what happened in the domain
 *
 * Lifecycle:
 * 1. Attribution::create() records this event
 * 2. Repository saves aggregate + flush
 * 3. DomainEventPublisherListener publishes after successful commit
 * 4. EventStore stores event (audit trail)
 * 5. Symfony EventSubscribers react:
 *    - Send email (sync)
 *    - Dispatch Messenger message for PDF generation (async)
 */
final readonly class GiftAttributed implements DomainEvent
{
    public function __construct(
        public string $attributionId,
        public string $habitantId,
        public string $habitantName,
        public string $habitantEmail,
        public string $giftId,
        public string $giftName,
        public \DateTimeImmutable $attributedAt
    ) {
    }

    public function aggregateId(): string
    {
        return $this->attributionId;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->attributedAt;
    }
}
