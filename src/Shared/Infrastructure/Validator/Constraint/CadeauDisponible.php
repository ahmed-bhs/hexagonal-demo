<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Custom Constraint: Cadeau Disponible
 *
 * Validates that a Cadeau (gift) is available for attribution.
 * This is an Infrastructure concern that delegates to Domain logic.
 *
 * ✅ HEXAGONAL ARCHITECTURE:
 * - Infrastructure validator (uses Symfony Validator)
 * - Delegates to Domain logic (Cadeau::peutEtreAttribue())
 * - Provides early feedback (UI validation)
 *
 * Usage:
 * #[CadeauDisponible]
 * public string $cadeauId;
 *
 * Note: This is a PRELIMINARY validation (fast feedback).
 * The FINAL validation happens in the Handler (atomic, in transaction).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class CadeauDisponible extends Constraint
{
    public string $message = 'Le cadeau "{{ nom }}" n\'est pas disponible (stock épuisé)';
    public string $notFoundMessage = 'Le cadeau avec l\'ID "{{ id }}" est introuvable';

    public function validatedBy(): string
    {
        return CadeauDisponibleValidator::class;
    }
}
