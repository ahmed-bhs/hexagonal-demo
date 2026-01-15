<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\Exception;

use App\Shared\Domain\Validation\ValidationError;

/**
 * Application Exception: Invalid Gift Request.
 *
 * Thrown when a gift request command fails validation.
 * Contains structured validation errors for API/UI feedback.
 *
 * This is APPLICATION-level because it's about use case validation,
 * not domain invariants.
 *
 * Example usage in controller:
 * ```php
 * try {
 *     $this->commandBus->dispatch($command);
 * } catch (InvalidDemandeCadeauException $e) {
 *     return new JsonResponse([
 *         'error' => 'Validation failed',
 *         'violations' => $e->getViolations()
 *     ], 422);
 * }
 * ```
 */
final class InvalidDemandeCadeauException extends \InvalidArgumentException
{
    /**
     * @param ValidationError[] $errors
     */
    public function __construct(
        private readonly array $errors
    ) {
        $message = sprintf(
            'Invalid gift request: %d validation error(s)',
            count($errors)
        );

        parent::__construct($message);
    }

    /**
     * @return ValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get violations formatted for API response.
     *
     * @return array<array{field: string, message: string}>
     */
    public function getViolations(): array
    {
        return array_map(
            fn(ValidationError $error) => [
                'field' => $error->field,
                'message' => $error->message,
            ],
            $this->errors
        );
    }

    /**
     * Check if a specific field has validation errors.
     */
    public function hasErrorForField(string $field): bool
    {
        foreach ($this->errors as $error) {
            if ($error->field === $field) {
                return true;
            }
        }

        return false;
    }
}
