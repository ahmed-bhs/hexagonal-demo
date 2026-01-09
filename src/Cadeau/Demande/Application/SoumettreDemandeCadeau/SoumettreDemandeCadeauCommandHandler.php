<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\SoumettreDemandeCadeau;

use App\Cadeau\Demande\Domain\Model\DemandeCadeau;
use App\Cadeau\Demande\Domain\Port\DemandeCadeauRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Command Handler.
 *
 * Handles the execution of SoumettreDemandeCadeauCommand.
 * Contains the business logic for this write operation.
 */
#[AsMessageHandler]
final readonly class SoumettreDemandeCadeauCommandHandler
{
    public function __construct(
        private DemandeCadeauRepositoryInterface $demandeCadeauRepository,
    ) {
    }

    public function __invoke(SoumettreDemandeCadeauCommand $command): void
    {
        $demande = DemandeCadeau::create(
            id: Uuid::v4()->toRfc4122(),
            nomDemandeur: $command->nomDemandeur,
            emailDemandeur: $command->emailDemandeur,
            telephoneDemandeur: $command->telephoneDemandeur,
            cadeauSouhaite: $command->cadeauSouhaite,
            motivation: $command->motivation,
        );

        $this->demandeCadeauRepository->save($demande);
    }
}
