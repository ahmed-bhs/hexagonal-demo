<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\Messaging\GenerateGiftCertificate;

/**
 * Async Command: Generate Gift Certificate PDF.
 *
 * This command is dispatched asynchronously via Symfony Messenger
 * to generate a PDF certificate for a gift attribution.
 *
 * Why async?
 * - PDF generation is a slow operation (can take seconds)
 * - Doesn't block the user's HTTP response
 * - Can be retried if it fails
 * - Can be scaled with multiple workers
 *
 * Flow:
 * 1. GiftAttributed domain event is published (sync)
 * 2. GiftAttributedSubscriber catches event (sync)
 * 3. Subscriber dispatches THIS command to Messenger queue (async)
 * 4. Worker picks up command from queue
 * 5. GenerateGiftCertificateCommandHandler generates PDF
 * 6. PDF is stored and email sent with attachment
 *
 * Message broker options:
 * - Doctrine (default, uses database table)
 * - Redis (fast, volatile)
 * - RabbitMQ (production-ready)
 * - Amazon SQS (cloud-native)
 */
final readonly class GenerateGiftCertificateCommand
{
    public function __construct(
        public string $attributionId,
        public string $habitantName,
        public string $habitantEmail,
        public string $giftName,
        public string $attributedAt
    ) {
    }
}
