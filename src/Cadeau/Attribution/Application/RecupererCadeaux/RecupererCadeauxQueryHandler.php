<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\RecupererCadeaux;

use App\Cadeau\Attribution\Domain\Port\CadeauRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Query Handler.
 *
 * Handles the execution of RecupererCadeauxQuery.
 * Retrieves all cadeaux from the repository.
 *
 * In hexagonal architecture:
 * - Part of Application layer
 * - Orchestrates Domain operations
 * - Depends on Domain Ports (interfaces), not concrete implementations
 */
#[AsMessageHandler]
final readonly class RecupererCadeauxQueryHandler
{
    public function __construct(
        private CadeauRepositoryInterface $cadeauRepository,
    ) {
    }

    public function __invoke(RecupererCadeauxQuery $query): RecupererCadeauxResponse
    {
        $cadeaux = $this->cadeauRepository->findAll();

        return new RecupererCadeauxResponse(
            cadeaux: $cadeaux,
        );
    }
}
