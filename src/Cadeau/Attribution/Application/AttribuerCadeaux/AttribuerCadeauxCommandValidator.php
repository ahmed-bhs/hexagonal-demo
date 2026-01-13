<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\AttribuerCadeaux;

use App\Shared\Domain\Validation\ValidationError;
use App\Shared\Domain\Validation\ValidationException;
use App\Shared\Domain\Validation\ValidatorInterface;

/**
 * Validateur Domain pour AttribuerCadeauxCommand.
 *
 * Validation pure (PHP) sans dépendance framework.
 * Respecte l'architecture hexagonale : logique métier dans le Domain.
 *
 * @implements ValidatorInterface<AttribuerCadeauxCommand>
 */
final readonly class AttribuerCadeauxCommandValidator implements ValidatorInterface
{
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function validate(object $command): array
    {
        assert($command instanceof AttribuerCadeauxCommand);

        $errors = [];

        // Validation habitantId
        if (empty($command->habitantId)) {
            $errors[] = new ValidationError('habitantId', 'Habitant ID cannot be empty');
        } elseif (!preg_match(self::UUID_PATTERN, $command->habitantId)) {
            $errors[] = new ValidationError('habitantId', 'Habitant ID must be a valid UUID');
        }

        // Validation cadeauId
        if (empty($command->cadeauId)) {
            $errors[] = new ValidationError('cadeauId', 'Cadeau ID cannot be empty');
        } elseif (!preg_match(self::UUID_PATTERN, $command->cadeauId)) {
            $errors[] = new ValidationError('cadeauId', 'Cadeau ID must be a valid UUID');
        }

        return $errors;
    }

    public function validateOrFail(object $command): void
    {
        $errors = $this->validate($command);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
