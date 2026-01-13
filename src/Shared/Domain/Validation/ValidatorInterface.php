<?php

declare(strict_types=1);

namespace App\Shared\Domain\Validation;

/**
 * Port pour la validation d'objets.
 *
 * Dans l'architecture hexagonale, cette interface (Port) est définie
 * dans le Domain et peut avoir plusieurs implémentations :
 * - Validateur Domain pur (PHP pur)
 * - Adaptateur Symfony Validator (Infrastructure)
 *
 * @template T
 */
interface ValidatorInterface
{
    /**
     * Valide un objet et retourne les erreurs de validation.
     *
     * @param T $object
     * @return ValidationError[]
     */
    public function validate(object $object): array;

    /**
     * Valide un objet et lève une exception si invalide.
     *
     * @param T $object
     * @throws ValidationException
     */
    public function validateOrFail(object $object): void;
}
