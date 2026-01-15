<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\EventSubscriber;

use App\Cadeau\Attribution\Infrastructure\Messaging\GenerateGiftCertificate\GenerateGiftCertificateCommand;
use App\Cadeau\Attribution\Domain\Event\GiftAttributed;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

/**
 * Event Subscriber for GiftAttributed domain event.
 *
 * This subscriber demonstrates TWO different approaches:
 * 1. SYNCHRONOUS: Send immediate email confirmation
 * 2. ASYNCHRONOUS: Dispatch PDF generation to Messenger queue
 *
 * Architecture Flow:
 * 1. Domain: GiftAttributed event recorded in Attribution aggregate
 * 2. Infrastructure: DomainEventPublisherListener publishes after flush
 * 3. Infrastructure: THIS subscriber catches the event
 * 4. Infrastructure: Performs SYNC operations (email)
 * 5. Infrastructure: Dispatches ASYNC operations to Messenger (PDF)
 *
 * Why split sync/async?
 * - Email confirmation is fast → send immediately (user sees it)
 * - PDF generation is slow → queue it (don't block user)
 *
 * Messenger benefits:
 * ✅ Async processing (fast HTTP response)
 * ✅ Automatic retries on failure
 * ✅ Scalable with multiple workers
 * ✅ Monitoring via Messenger stats
 * ✅ Can use different transports (Redis, RabbitMQ, SQS)
 */
final readonly class GiftAttributedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GiftAttributed::class => 'onGiftAttributed',
        ];
    }

    /**
     * Handle GiftAttributed domain event.
     *
     * Performs both sync and async operations:
     * 1. Send confirmation email (SYNC - fast)
     * 2. Dispatch PDF generation to queue (ASYNC - slow)
     */
    public function onGiftAttributed(GiftAttributed $event): void
    {
        $this->logger->info('Gift attributed', [
            'attributionId' => $event->attributionId,
            'habitantName' => $event->habitantName,
            'giftName' => $event->giftName,
        ]);

        // SYNC: Send immediate email confirmation
        $this->sendConfirmationEmail($event);

        // ASYNC: Dispatch PDF generation to Messenger queue
        $this->dispatchPdfGeneration($event);
    }

    /**
     * Send immediate email confirmation (SYNCHRONOUS).
     *
     * This is fast and doesn't require queueing.
     */
    private function sendConfirmationEmail(GiftAttributed $event): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($event->habitantEmail)
            ->subject('Gift Attribution Confirmation')
            ->html(sprintf(
                '<h1>Congratulations %s!</h1>
                <p>A gift has been attributed to you: <strong>%s</strong></p>
                <p>You will receive your gift certificate shortly by email.</p>
                <p>Attribution ID: %s</p>',
                htmlspecialchars($event->habitantName),
                htmlspecialchars($event->giftName),
                htmlspecialchars($event->attributionId)
            ));

        try {
            $this->mailer->send($email);

            $this->logger->info('Confirmation email sent', [
                'attributionId' => $event->attributionId,
                'email' => $event->habitantEmail,
            ]);
        } catch (\Throwable $e) {
            // Log but don't throw - we don't want to rollback the transaction
            $this->logger->error('Failed to send confirmation email', [
                'attributionId' => $event->attributionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch PDF generation to Messenger queue (ASYNCHRONOUS).
     *
     * The command will be processed by a worker in the background.
     * If it fails, Messenger will automatically retry.
     */
    private function dispatchPdfGeneration(GiftAttributed $event): void
    {
        $command = new GenerateGiftCertificateCommand(
            attributionId: $event->attributionId,
            habitantName: $event->habitantName,
            habitantEmail: $event->habitantEmail,
            giftName: $event->giftName,
            attributedAt: $event->attributedAt->format('Y-m-d H:i:s')
        );

        try {
            // Dispatch to Messenger queue (async transport)
            // By default, goes to 'async' transport defined in messenger.yaml
            $this->messageBus->dispatch($command);

            $this->logger->info('PDF generation dispatched to Messenger', [
                'attributionId' => $event->attributionId,
            ]);
        } catch (\Throwable $e) {
            // Log error - Messenger will handle retries
            $this->logger->error('Failed to dispatch PDF generation', [
                'attributionId' => $event->attributionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
