<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Command\AttribuerCadeau;

/**
 * CQRS Command.
 *
 * Represents an intention to perform a write operation.
 * Commands should be immutable and contain all the data needed to execute the action.
 */
final readonly class AttribuerCadeauCommand
{
    public function __construct(
        public string $habitantId,
        public string $cadeauId,
    ) {
    }
}
