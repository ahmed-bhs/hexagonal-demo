<?php

declare(strict_types=1);

namespace App\Security\User\Infrastructure\EventSubscriber;

use App\Security\User\Domain\Event\UserRegistered;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * User Registered Subscriber
 *
 * Reacts to UserRegistered domain event.
 * Infrastructure layer: sends welcome email.
 */
final readonly class UserRegisteredSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        // private MailerInterface $mailer, // Uncomment when mailer is configured
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => 'onUserRegistered',
        ];
    }

    public function onUserRegistered(UserRegistered $event): void
    {
        // Log registration
        $this->logger->info('New user registered', [
            'userId' => $event->userId,
            'email' => $event->email,
        ]);

        // TODO: Send welcome email
        // $this->mailer->send(...);
    }
}
