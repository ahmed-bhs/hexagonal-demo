<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Query\RecupererStatistiques;

/**
 * Query Response DTO.
 *
 * Data Transfer Object for returning statistics data.
 * Contains only the data needed by the presentation layer.
 */
final readonly class RecupererStatistiquesResponse
{
    public function __construct(
        public int $totalHabitants,
        public int $totalCadeaux,
        public int $totalAttributions,
        public int $habitantsEnfants,
        public int $habitantsAdultes,
        public int $habitantsSeniors,
    ) {
    }
}
