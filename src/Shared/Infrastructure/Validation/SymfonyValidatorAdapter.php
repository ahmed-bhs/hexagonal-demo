<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validation;

use App\Shared\Domain\Validation\ValidationError;
use App\Shared\Domain\Validation\ValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Adaptateur Symfony Validator pour ValidatorInterface (Port).
 *
 * Architecture hexagonale :
 * - Port : ValidatorInterface (Domain)
 * - Adapter : SymfonyValidatorAdapter (Infrastructure)
 *
 * Permet d'utiliser les contraintes Symfony (NotBlank, Email, Uuid, etc.)
 * tout en gardant le Domain pur (pas de dépendance Symfony).
 *
 * @template T
 * @implements ValidatorInterface<T>
 */
final readonly class SymfonyValidatorAdapter implements ValidatorInterface
{
    public function __construct(
        private SymfonyValidatorInterface $validator,
    ) {
    }

    public function validate(object $object): array
    {
        $violations = $this->validator->validate($object);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = new ValidationError(
                (string) $violation->getPropertyPath(),
                (string) $violation->getMessage()
            );
        }

        return $errors;
    }

    public function validateOrFail(object $object): void
    {
        $errors = $this->validate($object);

        if (count($errors) > 0) {
            throw new \App\Shared\Domain\Validation\ValidationException($errors);
        }
    }
}
