<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\Service;

use App\Cadeau\Attribution\Application\Command\AttribuerCadeau\AttribuerCadeauCommand;
use App\Cadeau\Attribution\Application\DTO\AttributionResultDTO;
use App\Cadeau\Attribution\Application\Exception\NoEligibleGiftException;
use App\Cadeau\Attribution\Application\Query\RecupererCadeaux\RecupererCadeauxQuery;
use App\Cadeau\Attribution\Application\Query\RecupererCadeaux\RecupererCadeauxResponse;
use App\Cadeau\Attribution\Domain\Port\AttributionRepositoryInterface;
use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Application Service: Automatic Gift Attribution.
 *
 * This is an Application Service (NOT a Domain Service).
 * It orchestrates multiple use cases to achieve a complex workflow.
 *
 * What is an Application Service?
 * - Orchestrates Commands and Queries
 * - Coordinates multiple aggregates
 * - Contains NO business logic (delegates to Domain)
 * - Manages transactions and use case flow
 * - Can use multiple repositories (read-only for queries)
 *
 * When to use Application Service?
 * ✅ Complex workflow across multiple use cases
 * ✅ Need to query before executing command
 * ✅ Orchestration logic (not business logic)
 * ❌ Simple CRUD (use Command/Query directly)
 * ❌ Business rules (belongs in Domain)
 *
 * Example use case:
 * "Automatically attribute the best available gift to a resident
 * based on their age, previous attributions, and available stock"
 *
 * Flow:
 * 1. Query available gifts
 * 2. Query resident's attribution history
 * 3. Apply business rules (via Domain)
 * 4. Select best match
 * 5. Execute attribution command
 * 6. Return result
 */
final readonly class AutomaticGiftAttributionService
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private HabitantRepositoryInterface $habitantRepository,
        private AttributionRepositoryInterface $attributionRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Automatically attribute a gift to a resident.
     *
     * This method orchestrates multiple steps:
     * - Query available gifts
     * - Check resident eligibility
     * - Select best match
     * - Execute attribution
     *
     * @throws NoEligibleGiftException If no suitable gift found
     * @throws \InvalidArgumentException If resident not found
     */
    public function attributeBestGift(string $habitantId): AttributionResultDTO
    {
        $this->logger->info('Starting automatic gift attribution', [
            'habitantId' => $habitantId,
        ]);

        // Step 1: Validate resident exists
        $habitant = $this->habitantRepository->findById($habitantId);
        if (!$habitant) {
            throw new \InvalidArgumentException(
                sprintf('Habitant with ID "%s" not found', $habitantId)
            );
        }

        // Step 2: Query all available gifts
        $query = new RecupererCadeauxQuery(
            page: 1,
            perPage: 100,
            searchTerm: ''
        );

        $envelope = $this->queryBus->dispatch($query);
        /** @var RecupererCadeauxResponse $response */
        $response = $envelope->last(HandledStamp::class)?->getResult();

        if (empty($response->cadeaux)) {
            throw new NoEligibleGiftException('No gifts available in stock');
        }

        // Step 3: Check attribution history (business rule: max 3 per year)
        $attributionsThisYear = $this->attributionRepository
            ->countForHabitantThisYear($habitantId);

        if ($attributionsThisYear >= 3) {
            throw new NoEligibleGiftException(
                'Resident has already received maximum gifts this year (3)'
            );
        }

        // Step 4: Select best match based on age and availability
        // This is orchestration logic, NOT business logic
        // Business rules are in Domain (Habitant, Cadeau)
        $bestGift = $this->selectBestGiftForResident($habitant, $response->cadeaux);

        if (!$bestGift) {
            throw new NoEligibleGiftException(
                'No eligible gift found for this resident'
            );
        }

        // Step 5: Execute attribution command
        $command = new AttribuerCadeauCommand(
            habitantId: $habitantId,
            cadeauId: $bestGift['id']
        );

        $this->commandBus->dispatch($command);

        $this->logger->info('Automatic gift attribution completed', [
            'habitantId' => $habitantId,
            'giftId' => $bestGift['id'],
            'giftName' => $bestGift['nom'],
        ]);

        // Step 6: Return result DTO
        return new AttributionResultDTO(
            success: true,
            habitantId: $habitantId,
            habitantName: $habitant->getPrenom() . ' ' . $habitant->getNom(),
            giftId: $bestGift['id'],
            giftName: $bestGift['nom'],
            attributedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Select best gift for resident based on age and availability.
     *
     * This is ORCHESTRATION logic (Application concern).
     * Business rules like "isAdult()" belong in Domain.
     *
     * @param array<array{id: string, nom: string, quantite: int}> $availableGifts
     * @return array{id: string, nom: string, quantite: int}|null
     */
    private function selectBestGiftForResident(
        \App\Cadeau\Attribution\Domain\Model\Habitant $habitant,
        array $availableGifts
    ): ?array {
        // Filter gifts with stock > 0
        $inStock = array_filter(
            $availableGifts,
            fn(array $gift) => $gift['quantite'] > 0
        );

        if (empty($inStock)) {
            return null;
        }

        // Business rule delegation: Check age eligibility
        // (In real app, this would be in Domain Specification)
        $age = $habitant->getAge();

        if ($age->isChild()) {
            // Prefer toys for children
            foreach ($inStock as $gift) {
                if (str_contains(strtolower($gift['nom']), 'jouet')) {
                    return $gift;
                }
            }
        } elseif ($age->isSenior()) {
            // Prefer books for seniors
            foreach ($inStock as $gift) {
                if (str_contains(strtolower($gift['nom']), 'livre')) {
                    return $gift;
                }
            }
        }

        // Default: return first available gift
        return reset($inStock) ?: null;
    }

    /**
     * Bulk attribution for multiple residents.
     *
     * Example of batch orchestration.
     *
     * @param string[] $habitantIds
     * @return AttributionResultDTO[]
     */
    public function attributeGiftsInBulk(array $habitantIds): array
    {
        $results = [];

        foreach ($habitantIds as $habitantId) {
            try {
                $results[] = $this->attributeBestGift($habitantId);
            } catch (NoEligibleGiftException|\InvalidArgumentException $e) {
                $this->logger->warning('Failed to attribute gift in bulk', [
                    'habitantId' => $habitantId,
                    'error' => $e->getMessage(),
                ]);

                $results[] = AttributionResultDTO::failure(
                    habitantId: $habitantId,
                    reason: $e->getMessage()
                );
            }
        }

        return $results;
    }
}
