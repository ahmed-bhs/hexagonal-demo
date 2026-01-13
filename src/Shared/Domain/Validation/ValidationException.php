<?php

declare(strict_types=1);

namespace App\Shared\Domain\Validation;

/**
 * Exception levée lors d'erreurs de validation.
 */
final class ValidationException extends \DomainException
{
    /**
     * @param ValidationError[] $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed',
    ) {
        parent::__construct($message);
    }

    /**
     * @return ValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?ValidationError
    {
        return $this->errors[0] ?? null;
    }
}
