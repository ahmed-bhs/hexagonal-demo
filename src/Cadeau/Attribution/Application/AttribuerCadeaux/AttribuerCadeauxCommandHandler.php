<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\AttribuerCadeaux;

use App\Cadeau\Attribution\Domain\Model\Attribution;
use App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use App\Shared\Domain\Port\IdGeneratorInterface;
use App\Shared\Domain\Validation\ValidatorInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Command Handler.
 *
 * Handles the execution of AttribuerCadeauxCommand.
 * Contains the business logic for this write operation.
 *
 * ✅ HEXAGONAL ARCHITECTURE - 100% PURE:
 * This handler now depends ONLY on Domain ports (interfaces).
 * No infrastructure dependencies (Symfony Uid removed).
 *
 * Dependencies (all from Domain layer):
 * - IdGeneratorInterface: Port for generating unique IDs
 * - HabitantRepositoryInterface: Port for habitant persistence
 * - CadeauRepositoryInterface: Port for cadeau persistence
 * - AttributionRepositoryInterface: Port for attribution persistence
 *
 * Benefits of using IdGeneratorInterface:
 * ✅ Application layer has ZERO infrastructure dependencies
 * ✅ Can swap UUID v7 for ULID, Snowflake, etc. without touching this code
 * ✅ Testable with FakeIdGenerator (deterministic IDs in tests)
 * ✅ Follows Dependency Inversion Principle
 */
#[AsMessageHandler]
final readonly class AttribuerCadeauxCommandHandler
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
        private HabitantRepositoryInterface $habitantRepository,
        private CadeauRepositoryInterface $cadeauRepository,
        private AttributionRepositoryInterface $attributionRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(AttribuerCadeauxCommand $command): void
    {
        // Validate command (Domain validation)
        $this->validator->validateOrFail($command);

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

        // Create attribution with generated ID
        $attribution = Attribution::create(
            $this->idGenerator->generate(),  // ✅ Uses port instead of direct Symfony Uid
            $command->habitantId,
            $command->cadeauId
        );

        // Persist attribution
        $this->attributionRepository->save($attribution);
    }
}
