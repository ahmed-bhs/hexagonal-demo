<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Exception;

/**
 * Application Exception: No Eligible Gift Found.
 *
 * This is an APPLICATION-level exception (NOT Domain).
 *
 * Difference between Application vs Domain exceptions:
 *
 * DOMAIN Exceptions:
 * - Invariant violations (Email format invalid)
 * - Business rule violations (Age < 0)
 * - Aggregate consistency (Cannot approve rejected request)
 * - Thrown by: Domain entities, value objects
 * - Example: InvalidEmailException, NegativeAgeException
 *
 * APPLICATION Exceptions:
 * - Use case failures (No gift available for attribution)
 * - Workflow errors (Cannot complete operation)
 * - Orchestration failures (External service unavailable)
 * - Thrown by: Command handlers, application services
 * - Example: NoEligibleGiftException, QuotaExceededException
 *
 * This exception is thrown when:
 * - No gifts available in stock
 * - No gifts match resident's criteria
 * - Resident has exceeded quota
 * - Business policy prevents attribution
 */
final class NoEligibleGiftException extends \DomainException
{
    public static function noStock(): self
    {
        return new self('No gifts available in stock');
    }

    public static function quotaExceeded(int $maxGifts): self
    {
        return new self(
            sprintf('Resident has already received maximum gifts this year (%d)', $maxGifts)
        );
    }

    public static function noCriteriaMatch(string $reason): self
    {
        return new self(
            sprintf('No eligible gift found: %s', $reason)
        );
    }
}
