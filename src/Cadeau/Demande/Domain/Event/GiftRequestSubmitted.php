<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Domain Event raised when a gift request is successfully submitted.
 *
 * This event follows hexagonal architecture principles:
 * - Defined in the Domain layer (pure business logic)
 * - No dependency on any framework (no Symfony EventDispatcher here)
 * - Carries only necessary business data
 * - Implements DomainEvent interface from Shared Kernel
 *
 * Event naming convention:
 * - Past tense (GiftRequestSubmitted, not SubmitGiftRequest)
 * - Describes what happened, not what should happen
 *
 * Lifecycle:
 * 1. Created by DemandeCadeau aggregate during business operation
 * 2. Collected in aggregate's event collection (via AggregateRoot trait)
 * 3. Pulled by infrastructure after successful DB flush
 * 4. Published to subscribers (email, notifications, EventStore)
 *
 * Possible subscribers:
 * - Send confirmation email to requester
 * - Notify administrator
 * - Store in EventStore for audit trail
 * - Update read model / statistics
 */
final readonly class GiftRequestSubmitted implements DomainEvent
{
    public function __construct(
        public string $giftRequestId,
        public string $requesterName,
        public string $requesterEmail,
        public string $requestedGift,
        public \DateTimeImmutable $submittedAt
    ) {
    }

    public function aggregateId(): string
    {
        return $this->giftRequestId;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }
}
