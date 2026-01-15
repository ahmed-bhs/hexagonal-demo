<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\Command\SoumettreDemandeCadeau;

/**
 * CQRS Command.
 *
 * Represents an intention to perform a write operation.
 * Commands should be immutable and contain all the data needed to execute the action.
 *
 * ✅ HEXAGONAL ARCHITECTURE - 100% PURE:
 * Pas de dépendance Symfony Validator (pas d'attributs #[Assert]).
 * Les contraintes de validation sont externalisées dans config/validator/demande_cadeau_command.yaml.
 * Cela respecte la séparation Domain/Application (pur) vs Infrastructure (Symfony).
 */
final readonly class SoumettreDemandeCadeauCommand
{
    public function __construct(
        public string $nomDemandeur,
        public string $emailDemandeur,
        public string $telephoneDemandeur,
        public string $cadeauSouhaite,
        public string $motivation,
    ) {
    }
}
