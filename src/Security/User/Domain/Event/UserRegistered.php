<?php

declare(strict_types=1);

namespace App\Security\User\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Domain Event: User Registered
 *
 * Fired when a new user successfully registers.
 * Can trigger: welcome email, analytics, etc.
 */
final readonly class UserRegistered implements DomainEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public \DateTimeImmutable $occurredAt,
    ) {}

    public function aggregateId(): string
    {
        return $this->userId;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
