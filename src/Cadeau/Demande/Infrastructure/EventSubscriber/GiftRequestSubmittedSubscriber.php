<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Infrastructure\EventSubscriber;

use App\Cadeau\Demande\Domain\Event\GiftRequestSubmitted;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Symfony EventSubscriber that catches Domain events from Infrastructure layer.
 *
 * Hexagonal Architecture Flow:
 * 1. Domain: GiftRequestSubmitted event is defined (pure PHP, no framework)
 * 2. Application: CommandHandler dispatches event via EventDispatcherInterface port
 * 3. Infrastructure: SymfonyEventDispatcherAdapter bridges to Symfony EventDispatcher
 * 4. Infrastructure: THIS subscriber catches the event and performs technical operations
 *
 * This is a SECONDARY ADAPTER - it reacts to domain events to perform infrastructure tasks:
 * - Send emails (Symfony Mailer)
 * - Log operations (Psr Logger)
 * - Send notifications to external systems
 * - Update read models / projections
 *
 * Benefits:
 * ✅ Domain layer knows nothing about email sending
 * ✅ Application layer is not coupled to technical concerns
 * ✅ Easy to add/remove side effects without touching business logic
 * ✅ Can have multiple subscribers for the same event (email, SMS, analytics, etc.)
 */
final readonly class GiftRequestSubmittedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Subscribe to domain events by their FQCN.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            GiftRequestSubmitted::class => 'onGiftRequestSubmitted',
        ];
    }

    /**
     * Handle the GiftRequestSubmitted domain event.
     *
     * This method is triggered automatically when the event is dispatched
     * from the Application layer.
     */
    public function onGiftRequestSubmitted(GiftRequestSubmitted $event): void
    {
        $this->logger->info('Gift request submitted', [
            'giftRequestId' => $event->giftRequestId,
            'requesterName' => $event->requesterName,
            'requesterEmail' => $event->requesterEmail,
            'requestedGift' => $event->requestedGift,
        ]);

        // Send confirmation email to the requester
        $this->sendConfirmationEmail($event);

        // You could also:
        // - Send notification to admin
        // - Update statistics
        // - Send to external CRM
        // - Trigger webhook
    }

    /**
     * Send a confirmation email to the gift requester.
     */
    private function sendConfirmationEmail(GiftRequestSubmitted $event): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($event->requesterEmail)
            ->subject('Gift Request Confirmation')
            ->html($this->buildEmailContent($event));

        try {
            $this->mailer->send($email);

            $this->logger->info('Confirmation email sent', [
                'giftRequestId' => $event->giftRequestId,
                'email' => $event->requesterEmail,
            ]);
        } catch (\Throwable $e) {
            // Log error but don't throw - we don't want to rollback the transaction
            // if email fails. The gift request is already saved.
            $this->logger->error('Failed to send confirmation email', [
                'giftRequestId' => $event->giftRequestId,
                'email' => $event->requesterEmail,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the email HTML content.
     */
    private function buildEmailContent(GiftRequestSubmitted $event): string
    {
        return sprintf(
            '<h1>Gift Request Confirmation</h1>
            <p>Dear %s,</p>
            <p>Thank you for submitting your gift request!</p>
            <h2>Details:</h2>
            <ul>
                <li><strong>Request ID:</strong> %s</li>
                <li><strong>Requested Gift:</strong> %s</li>
                <li><strong>Submitted At:</strong> %s</li>
            </ul>
            <p>We will review your request and get back to you soon.</p>
            <p>Best regards,<br>The Gift Team</p>',
            htmlspecialchars($event->requesterName),
            htmlspecialchars($event->giftRequestId),
            htmlspecialchars($event->requestedGift),
            $event->submittedAt->format('Y-m-d H:i:s')
        );
    }
}
