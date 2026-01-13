<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\SoumettreDemandeCadeau;

use App\Cadeau\Demande\Domain\Model\DemandeCadeau;
use App\Cadeau\Demande\Domain\Port\DemandeCadeauRepositoryInterface;
use App\Shared\Domain\Port\IdGeneratorInterface;
use App\Shared\Domain\Validation\ValidatorInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Command Handler.
 *
 * Handles the execution of SoumettreDemandeCadeauCommand.
 * Contains the business logic for this write operation.
 *
 * ✅ HEXAGONAL ARCHITECTURE - 100% PURE:
 * This handler now depends ONLY on Domain ports (interfaces).
 * No infrastructure dependencies (Symfony Uid removed).
 *
 * Dependencies (all from Domain layer):
 * - IdGeneratorInterface: Port for generating unique IDs
 * - DemandeCadeauRepositoryInterface: Port for demande cadeau persistence
 * - ValidatorInterface: Port for validation (uses Symfony Validator via adapter)
 *
 * Benefits of using IdGeneratorInterface:
 * ✅ Application layer has ZERO infrastructure dependencies
 * ✅ Can swap UUID v7 for ULID, Snowflake, etc. without touching this code
 * ✅ Testable with FakeIdGenerator (deterministic IDs in tests)
 * ✅ Follows Dependency Inversion Principle
 */
#[AsMessageHandler]
final readonly class SoumettreDemandeCadeauCommandHandler
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
        private DemandeCadeauRepositoryInterface $demandeCadeauRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(SoumettreDemandeCadeauCommand $command): void
    {
        // Validate command (using Symfony Validator via adapter)
        $this->validator->validateOrFail($command);

        $demande = DemandeCadeau::create(
            id: $this->idGenerator->generate(),  // ✅ Uses port instead of direct Symfony Uid
            nomDemandeur: $command->nomDemandeur,
            emailDemandeur: $command->emailDemandeur,
            telephoneDemandeur: $command->telephoneDemandeur,
            cadeauSouhaite: $command->cadeauSouhaite,
            motivation: $command->motivation,
        );

        $this->demandeCadeauRepository->save($demande);
    }
}
