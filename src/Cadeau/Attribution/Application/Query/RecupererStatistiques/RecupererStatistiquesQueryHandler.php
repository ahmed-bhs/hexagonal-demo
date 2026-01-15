<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Query\RecupererStatistiques;

use App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Query Handler.
 *
 * Handles the execution of RecupererStatistiquesQuery.
 * Retrieves statistics data from multiple repositories.
 *
 * In hexagonal architecture:
 * - Part of Application layer
 * - Orchestrates Domain operations
 * - Depends on Domain Ports (interfaces), not concrete implementations
 */
#[AsMessageHandler]
final readonly class RecupererStatistiquesQueryHandler
{
    public function __construct(
        private HabitantRepositoryInterface $habitantRepository,
        private CadeauRepositoryInterface $cadeauRepository,
        private AttributionRepositoryInterface $attributionRepository,
    ) {
    }

    public function __invoke(RecupererStatistiquesQuery $query): RecupererStatistiquesResponse
    {
        $habitants = $this->habitantRepository->findAll();
        $cadeaux = $this->cadeauRepository->findAll();
        $attributions = $this->attributionRepository->findAll();

        $enfants = 0;
        $adultes = 0;
        $seniors = 0;

        foreach ($habitants as $habitant) {
            if ($habitant->isEnfant()) {
                $enfants++;
            } elseif ($habitant->isSenior()) {
                $seniors++;
            } else {
                $adultes++;
            }
        }

        return new RecupererStatistiquesResponse(
            totalHabitants: count($habitants),
            totalCadeaux: count($cadeaux),
            totalAttributions: count($attributions),
            habitantsEnfants: $enfants,
            habitantsAdultes: $adultes,
            habitantsSeniors: $seniors,
        );
    }
}
