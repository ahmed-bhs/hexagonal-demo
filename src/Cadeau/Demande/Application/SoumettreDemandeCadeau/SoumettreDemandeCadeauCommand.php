<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\SoumettreDemandeCadeau;

/**
 * CQRS Command.
 *
 * Represents an intention to perform a write operation.
 * Commands should be immutable and contain all the data needed to execute the action.
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
