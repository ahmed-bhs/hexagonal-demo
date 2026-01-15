<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Command\AttribuerCadeau;

use App\Cadeau\Attribution\Domain\Model\Attribution;
use App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use App\Shared\Domain\Port\IdGeneratorInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Command Handler.
 *
 * Handles the execution of AttribuerCadeauCommand.
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
final readonly class AttribuerCadeauCommandHandler
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
        private HabitantRepositoryInterface $habitantRepository,
        private CadeauRepositoryInterface $cadeauRepository,
        private AttributionRepositoryInterface $attributionRepository,
    ) {
    }

    public function __invoke(AttribuerCadeauCommand $command): void
    {
        // ✅ No validation needed - Value Objects guarantee validity
        // Command construction would have failed if IDs were invalid

        // Load entities
        $habitant = $this->habitantRepository->findById($command->habitantId);
        if (!$habitant) {
            throw new \InvalidArgumentException(
                sprintf('Habitant with ID "%s" not found', $command->habitantId->value())
            );
        }

        $cadeau = $this->cadeauRepository->findById($command->cadeauId);
        if (!$cadeau) {
            throw new \InvalidArgumentException(
                sprintf('Cadeau with ID "%s" not found', $command->cadeauId->value())
            );
        }

        // ✅ ATOMIC VALIDATION + STOCK DECREASE
        // This is the FINAL validation (inside transaction)
        // Protects against race conditions
        try {
            $cadeau->diminuerStock();  // ← Validates and decreases stock atomically
        } catch (\DomainException $e) {
            throw new \DomainException(
                sprintf(
                    'Cannot attribute gift "%s" to habitant "%s %s": %s',
                    $cadeau->getNom(),
                    $habitant->getPrenom(),
                    $habitant->getNom(),
                    $e->getMessage()
                ),
                previous: $e
            );
        }

        // Create attribution with full context for domain event
        // This allows the aggregate to record a complete GiftAttributed event
        $attribution = Attribution::createWithDetails(
            id: $this->idGenerator->generate(),
            habitantId: $command->habitantId->value(),
            habitantName: $habitant->getPrenom() . ' ' . $habitant->getNom(),
            habitantEmail: $habitant->getEmail()->value,
            cadeauId: $command->cadeauId->value(),
            cadeauName: $cadeau->getNom()
        );

        // Persist attribution AND updated cadeau (stock decreased)
        // After successful flush, DomainEventPublisherListener will:
        // 1. Pull GiftAttributed event from aggregate
        // 2. Store in EventStore (audit trail)
        // 3. Publish to Symfony EventDispatcher (email, PDF generation, etc.)
        // ✅ Transaction ensures: stock update + attribution creation are atomic
        $this->cadeauRepository->save($cadeau);  // ← Save updated stock
        $this->attributionRepository->save($attribution);
    }
}
