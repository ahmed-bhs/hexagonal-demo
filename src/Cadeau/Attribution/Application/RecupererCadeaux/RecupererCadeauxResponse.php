<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\RecupererCadeaux;

use App\Cadeau\Attribution\Domain\Model\Cadeau;

/**
 * Query Response DTO.
 *
 * Returns the list of all cadeaux.
 */
final readonly class RecupererCadeauxResponse
{
    /**
     * @param Cadeau[] $cadeaux
     */
    public function __construct(
        public array $cadeaux,
    ) {
    }
}
