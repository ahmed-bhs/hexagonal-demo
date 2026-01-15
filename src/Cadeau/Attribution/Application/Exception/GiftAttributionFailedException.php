<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Exception;

/**
 * Application Exception: Gift Attribution Failed.
 *
 * Thrown when the gift attribution process fails for various reasons.
 * This is a general application-level exception that wraps different failure scenarios.
 *
 * Use cases:
 * - Stock depleted during attribution
 * - Concurrent attribution conflict
 * - External system failure (payment, inventory)
 * - Transaction rollback
 *
 * This exception can be caught by controllers/CLI to provide
 * appropriate user feedback.
 */
final class GiftAttributionFailedException extends \RuntimeException
{
    private function __construct(
        string $message,
        private readonly string $habitantId,
        private readonly string $giftId,
        private readonly string $reason,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function stockDepleted(
        string $habitantId,
        string $giftId,
        string $giftName
    ): self {
        return new self(
            message: sprintf(
                'Gift attribution failed: "%s" is out of stock',
                $giftName
            ),
            habitantId: $habitantId,
            giftId: $giftId,
            reason: 'stock_depleted'
        );
    }

    public static function concurrentModification(
        string $habitantId,
        string $giftId
    ): self {
        return new self(
            message: 'Gift attribution failed: concurrent modification detected',
            habitantId: $habitantId,
            giftId: $giftId,
            reason: 'concurrent_modification'
        );
    }

    public static function fromException(
        string $habitantId,
        string $giftId,
        \Throwable $exception
    ): self {
        return new self(
            message: sprintf(
                'Gift attribution failed: %s',
                $exception->getMessage()
            ),
            habitantId: $habitantId,
            giftId: $giftId,
            reason: 'unknown_error',
            previous: $exception
        );
    }

    public function getHabitantId(): string
    {
        return $this->habitantId;
    }

    public function getGiftId(): string
    {
        return $this->giftId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get context for logging and error reporting.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [
            'habitantId' => $this->habitantId,
            'giftId' => $this->giftId,
            'reason' => $this->reason,
            'message' => $this->getMessage(),
        ];
    }
}
