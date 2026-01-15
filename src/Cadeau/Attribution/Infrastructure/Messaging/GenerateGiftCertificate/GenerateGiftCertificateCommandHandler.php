<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Infrastructure\Messaging\GenerateGiftCertificate;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

/**
 * Async Command Handler: Generate Gift Certificate PDF.
 *
 * This handler is executed by a Symfony Messenger worker (asynchronously).
 * It generates a PDF certificate and emails it to the recipient.
 *
 * Architecture:
 * - This is an Application layer handler
 * - Triggered by Messenger worker (not HTTP request)
 * - Long-running operation (PDF generation)
 * - Retryable if fails
 *
 * Benefits of async processing:
 * ✅ Doesn't block user's HTTP response
 * ✅ Automatic retries on failure (Messenger config)
 * ✅ Scalable (multiple workers)
 * ✅ Monitoring via Messenger stats
 *
 * In production, you would:
 * - Use a real PDF library (TCPDF, mPDF, wkhtmltopdf)
 * - Store PDF in cloud storage (S3, Google Cloud Storage)
 * - Use proper email templates
 * - Handle errors gracefully
 */
#[AsMessageHandler]
final readonly class GenerateGiftCertificateCommandHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(GenerateGiftCertificateCommand $command): void
    {
        $this->logger->info('Generating gift certificate PDF', [
            'attributionId' => $command->attributionId,
            'habitantName' => $command->habitantName,
            'giftName' => $command->giftName,
        ]);

        // Simulate PDF generation (in production, use a real PDF library)
        // Example: $pdf = $this->pdfGenerator->generate($command);
        usleep(500000); // Simulate 0.5 second processing time

        $pdfContent = $this->generatePdfContent($command);

        // Send email with PDF attachment
        $this->sendCertificateEmail($command, $pdfContent);

        $this->logger->info('Gift certificate PDF generated and emailed', [
            'attributionId' => $command->attributionId,
            'email' => $command->habitantEmail,
        ]);
    }

    /**
     * Generate PDF content (mocked for demo).
     *
     * In production, use:
     * - TCPDF: Pure PHP, feature-rich
     * - mPDF: HTML to PDF conversion
     * - wkhtmltopdf: HTML to PDF via binary
     * - Gotenberg: Docker-based PDF API
     */
    private function generatePdfContent(GenerateGiftCertificateCommand $command): string
    {
        return sprintf(
            "=== GIFT CERTIFICATE ===\n\n" .
            "Recipient: %s\n" .
            "Gift: %s\n" .
            "Date: %s\n\n" .
            "Congratulations on receiving your gift!\n" .
            "Attribution ID: %s\n",
            $command->habitantName,
            $command->giftName,
            $command->attributedAt,
            $command->attributionId
        );
    }

    /**
     * Send email with PDF attachment.
     */
    private function sendCertificateEmail(GenerateGiftCertificateCommand $command, string $pdfContent): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($command->habitantEmail)
            ->subject('Your Gift Certificate')
            ->html(sprintf(
                '<h1>Congratulations %s!</h1>
                <p>Your gift certificate for <strong>%s</strong> is attached to this email.</p>
                <p>Please print it and present it to receive your gift.</p>',
                htmlspecialchars($command->habitantName),
                htmlspecialchars($command->giftName)
            ))
            ->attach($pdfContent, 'gift-certificate.txt', 'text/plain');

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Log error and re-throw for Messenger retry
            $this->logger->error('Failed to send gift certificate email', [
                'error' => $e->getMessage(),
                'attributionId' => $command->attributionId,
            ]);

            throw $e; // Re-throw for Messenger to retry
        }
    }
}
