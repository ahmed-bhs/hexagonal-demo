<?php

declare(strict_types=1);

namespace App\Shared\Domain\Validation;

/**
 * Représente une erreur de validation.
 */
final readonly class ValidationError
{
    public function __construct(
        public string $field,
        public string $message,
    ) {
    }
}
