<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\RecupererHabitants;

use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use App\Shared\Pagination\Domain\ValueObject\Page;
use App\Shared\Pagination\Domain\ValueObject\PerPage;
use App\Shared\Search\Domain\ValueObject\SearchTerm;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Query Handler.
 *
 * Handles the execution of RecupererHabitantsQuery.
 * Retrieves data without modifying state.
 */
#[AsMessageHandler]
final readonly class RecupererHabitantsQueryHandler
{
    public function __construct(
        private HabitantRepositoryInterface $habitantRepository,
    ) {
    }

    public function __invoke(RecupererHabitantsQuery $query): RecupererHabitantsResponse
    {
        $page = new Page($query->page);
        $perPage = new PerPage($query->perPage);
        $searchTerm = new SearchTerm($query->searchTerm);

        $result = $this->habitantRepository->searchPaginated($searchTerm, $page, $perPage);

        return new RecupererHabitantsResponse(
            habitants: $result->items,
            currentPage: $result->currentPage->toInt(),
            perPage: $result->perPage->toInt(),
            total: $result->total->toInt(),
            totalPages: $result->getTotalPages(),
            hasNextPage: $result->hasNextPage(),
            hasPreviousPage: $result->hasPreviousPage(),
        );
    }
}
