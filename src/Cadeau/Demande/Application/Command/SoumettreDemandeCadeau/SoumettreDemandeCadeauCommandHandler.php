<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\Command\SoumettreDemandeCadeau;

use App\Cadeau\Demande\Domain\Model\DemandeCadeau;
use App\Cadeau\Demande\Domain\Port\DemandeCadeauRepositoryInterface;
use App\Shared\Domain\Port\IdGeneratorInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Command Handler: Submit Gift Request.
 *
 * Orchestrates the submission of a gift request use case.
 * Follows DDD patterns with automatic domain event publishing.
 *
 * ✅ HEXAGONAL ARCHITECTURE - 100% PURE:
 * This handler depends ONLY on Domain ports (interfaces).
 * No infrastructure dependencies. No manual event publishing.
 *
 * Dependencies (all from Domain layer):
 * - IdGeneratorInterface: Port for generating unique IDs
 * - DemandeCadeauRepositoryInterface: Port for gift request persistence
 *
 * Validation:
 * ✅ Done by ValidationMiddleware BEFORE this handler is called
 * ✅ Handler can assume command is already validated
 *
 * Domain Events Flow (Automatic):
 * 1. DemandeCadeau::create() records GiftRequestSubmitted event internally
 * 2. Repository saves aggregate (persist + flush)
 * 3. Doctrine Listener (DomainEventPublisherListener) triggers after flush
 * 4. Listener pulls events from aggregate
 * 5. Events published via DomainEventPublisherInterface
 * 6. EventSubscribers react (email, EventStore, logs)
 *
 * Benefits:
 * ✅ No manual event dispatch (automatic via infrastructure)
 * ✅ 100% transaction-safe (events published ONLY if commit succeeds)
 * ✅ Domain aggregates encapsulate their own events
 * ✅ CommandHandler stays simple and focused
 * ✅ Impossible to forget to publish events
 */
#[AsMessageHandler]
final readonly class SoumettreDemandeCadeauCommandHandler
{
    public function __construct(
        private IdGeneratorInterface $idGenerator,
        private DemandeCadeauRepositoryInterface $demandeCadeauRepository,
    ) {
    }

    public function __invoke(SoumettreDemandeCadeauCommand $command): void
    {
        // ✅ No validation needed - ValidationMiddleware already validated
        // If we reach here, the command is valid

        // Create aggregate - domain event is recorded internally
        // The aggregate (DemandeCadeau) records GiftRequestSubmitted event via AggregateRoot trait
        $demande = DemandeCadeau::create(
            id: $this->idGenerator->generate(),
            nomDemandeur: $command->nomDemandeur,
            emailDemandeur: $command->emailDemandeur,
            telephoneDemandeur: $command->telephoneDemandeur,
            cadeauSouhaite: $command->cadeauSouhaite,
            motivation: $command->motivation,
        );

        // Save aggregate (persist + flush)
        // After successful flush, DomainEventPublisherListener automatically:
        // 1. Pulls domain events from aggregate
        // 2. Publishes them via DomainEventPublisherInterface
        // 3. EventSubscribers react (email, EventStore, logs)
        // ✅ 100% transaction-safe: Events published ONLY if commit succeeds
        $this->demandeCadeauRepository->save($demande);
    }
}
