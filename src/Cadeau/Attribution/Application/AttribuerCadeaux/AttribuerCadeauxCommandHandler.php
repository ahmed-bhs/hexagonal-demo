<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\AttribuerCadeaux;

use App\Cadeau\Attribution\Domain\Model\Attribution;
use App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Command Handler.
 *
 * Handles the execution of AttribuerCadeauxCommand.
 * Contains the business logic for this write operation.
 */
#[AsMessageHandler]
final readonly class AttribuerCadeauxCommandHandler
{
    public function __construct(
        private HabitantRepositoryInterface $habitantRepository,
        private CadeauRepositoryInterface $cadeauRepository,
        private AttributionRepositoryInterface $attributionRepository,
    ) {
    }

    public function __invoke(AttribuerCadeauxCommand $command): void
    {
        // Validate that habitant exists
        $habitant = $this->habitantRepository->findById($command->habitantId);
        if (!$habitant) {
            throw new \InvalidArgumentException(sprintf('Habitant with ID "%s" not found', $command->habitantId));
        }

        // Validate that cadeau exists
        $cadeau = $this->cadeauRepository->findById($command->cadeauId);
        if (!$cadeau) {
            throw new \InvalidArgumentException(sprintf('Cadeau with ID "%s" not found', $command->cadeauId));
        }

        // Create attribution
        $attribution = Attribution::create(
            Uuid::v4()->toRfc4122(),
            $command->habitantId,
            $command->cadeauId
        );

        // Persist attribution
        $this->attributionRepository->save($attribution);
    }
}
