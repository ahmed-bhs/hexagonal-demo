<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Command\AttribuerCadeau;

use App\Cadeau\Attribution\Domain\ValueObject\CadeauId;
use App\Cadeau\Attribution\Domain\ValueObject\HabitantId;

/**
 * CQRS Command.
 *
 * Represents an intention to perform a write operation.
 * Commands should be immutable and contain all the data needed to execute the action.
 *
 * ✅ HEXAGONAL ARCHITECTURE - Value Objects:
 * Uses Value Objects instead of primitives for:
 * - Type safety (cannot pass wrong ID type)
 * - Self-validation (VOs validate at construction)
 * - Domain expressiveness (HabitantId vs string)
 * - No need for ValidatorInterface in handler
 */
final readonly class AttribuerCadeauCommand
{
    public function __construct(
        public HabitantId $habitantId,
        public CadeauId $cadeauId,
    ) {
        // ✅ Value Objects validate themselves at construction
        // If we reach here, the command is valid
    }
}
