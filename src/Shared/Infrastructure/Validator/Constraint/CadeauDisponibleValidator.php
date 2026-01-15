<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator\Constraint;

use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use App\Cadeau\Attribution\Domain\ValueObject\CadeauId;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Custom Validator: Cadeau Disponible
 *
 * Validates that a Cadeau (gift) is available for attribution.
 *
 * ✅ HEXAGONAL ARCHITECTURE - NO DUPLICATION:
 * This validator DELEGATES to Domain logic instead of duplicating the business rule.
 *
 * Flow:
 * 1. Load Cadeau entity from repository (Infrastructure)
 * 2. Call Cadeau::peutEtreAttribue() (Domain)
 * 3. If false -> validation error
 *
 * Benefits:
 * ✅ Business rule defined ONCE in Domain (Cadeau::peutEtreAttribue())
 * ✅ No duplication (validator uses Domain method)
 * ✅ Early feedback (fast validation before Handler)
 * ✅ Infrastructure concern (uses Symfony Validator + Repository)
 *
 * Note:
 * This is PRELIMINARY validation (fast feedback to user).
 * FINAL validation happens in Handler (atomic, in transaction).
 * This protects against race conditions.
 */
final class CadeauDisponibleValidator extends ConstraintValidator
{
    public function __construct(
        private CadeauRepositoryInterface $cadeauRepository
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof CadeauDisponible) {
            throw new UnexpectedTypeException($constraint, CadeauDisponible::class);
        }

        // Null/empty values are valid (use #[Assert\NotBlank] for required fields)
        if (null === $value || '' === $value) {
            return;
        }

        // Value should be a string (UUID)
        if (!is_string($value)) {
            return;
        }

        // Try to create CadeauId Value Object (validates UUID format)
        try {
            $cadeauId = new CadeauId($value);
        } catch (\InvalidArgumentException $e) {
            // Invalid UUID format - let #[Assert\Uuid] handle this
            return;
        }

        // Load Cadeau entity from repository
        $cadeau = $this->cadeauRepository->findById($cadeauId);

        if (!$cadeau) {
            $this->context
                ->buildViolation($constraint->notFoundMessage)
                ->setParameter('{{ id }}', $value)
                ->addViolation();
            return;
        }

        // ✅ DELEGATE TO DOMAIN - No duplication!
        // Business rule "stock > 0" is defined ONLY in Cadeau::peutEtreAttribue()
        if (!$cadeau->peutEtreAttribue()) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ nom }}', $cadeau->getNom())
                ->addViolation();
        }

        // ✅ If we reach here, preliminary validation passed
        // Note: Final validation happens in Handler (atomic, in transaction)
    }
}
