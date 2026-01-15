<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Validation Middleware
 *
 * Validates messages BEFORE they reach the handler.
 * This keeps handlers clean and focused on business logic.
 *
 * ✅ HEXAGONAL ARCHITECTURE:
 * - Infrastructure concern (uses Symfony Validator)
 * - Validation at the boundary (middleware)
 * - Handlers stay pure (no validation code)
 *
 * Flow:
 * Message → ValidationMiddleware → Handler
 *           ↓ (if invalid)
 *        ValidationFailedException
 */
final readonly class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        // Validate the message
        $violations = $this->validator->validate($message);

        if (count($violations) > 0) {
            throw new ValidationFailedException($message, $violations);
        }

        // Continue to next middleware/handler
        return $stack->next()->handle($envelope, $stack);
    }
}
