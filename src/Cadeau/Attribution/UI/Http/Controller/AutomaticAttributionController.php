<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\UI\Http\Controller;

use App\Cadeau\Attribution\Application\DTO\AttributionResultDTO;
use App\Cadeau\Attribution\Application\Exception\GiftAttributionFailedException;
use App\Cadeau\Attribution\Application\Exception\NoEligibleGiftException;
use App\Cadeau\Attribution\Application\Service\AutomaticGiftAttributionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller: Automatic Gift Attribution.
 *
 * This controller demonstrates how to use:
 * - Application Service (AutomaticGiftAttributionService)
 * - Application Exceptions (NoEligibleGiftException, etc.)
 * - DTOs (AttributionResultDTO)
 *
 * Architecture flow:
 * HTTP Request → Controller → Application Service → Domain → Infrastructure
 *                    ↓
 *                   DTO → JSON Response
 *
 * Benefits of this architecture:
 * ✅ Controller is thin (no business logic)
 * ✅ Service handles orchestration
 * ✅ DTOs provide clean API responses
 * ✅ Exceptions give structured error handling
 */
#[Route('/api/attributions', name: 'api_attributions_')]
final class AutomaticAttributionController extends AbstractController
{
    public function __construct(
        private readonly AutomaticGiftAttributionService $attributionService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Automatically attribute a gift to a resident.
     *
     * POST /api/attributions/automatic
     * Body: {"habitantId": "uuid"}
     *
     * Responses:
     * - 200: Gift attributed successfully
     * - 404: Resident not found
     * - 422: No eligible gift available
     * - 500: Internal server error
     */
    #[Route('/automatic', name: 'automatic', methods: ['POST'])]
    public function automatic(Request $request): JsonResponse
    {
        // Parse request
        $data = json_decode($request->getContent(), true);
        $habitantId = $data['habitantId'] ?? null;

        if (!$habitantId) {
            return new JsonResponse([
                'error' => 'Missing habitantId parameter',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Call Application Service
            // Service returns DTO (not Domain entity)
            $result = $this->attributionService->attributeBestGift($habitantId);

            // DTO can be serialized directly to JSON
            return new JsonResponse([
                'success' => true,
                'message' => 'Gift attributed successfully',
                'data' => $result->toArray(),
            ], Response::HTTP_OK);

        } catch (NoEligibleGiftException $e) {
            // Application exception: no eligible gift
            // Return structured error response
            return new JsonResponse([
                'error' => 'No eligible gift available',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\InvalidArgumentException $e) {
            // Application exception: invalid input
            return new JsonResponse([
                'error' => 'Invalid request',
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);

        } catch (GiftAttributionFailedException $e) {
            // Application exception: attribution failed
            $this->logger->error('Gift attribution failed', $e->getContext());

            return new JsonResponse([
                'error' => 'Attribution failed',
                'message' => $e->getMessage(),
                'reason' => $e->getReason(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Throwable $e) {
            // Unexpected exception
            $this->logger->error('Unexpected error during automatic attribution', [
                'habitantId' => $habitantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bulk automatic attribution for multiple residents.
     *
     * POST /api/attributions/automatic/bulk
     * Body: {"habitantIds": ["uuid1", "uuid2", ...]}
     *
     * Returns array of results (success and failures).
     */
    #[Route('/automatic/bulk', name: 'automatic_bulk', methods: ['POST'])]
    public function automaticBulk(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $habitantIds = $data['habitantIds'] ?? [];

        if (empty($habitantIds)) {
            return new JsonResponse([
                'error' => 'Missing habitantIds parameter',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (count($habitantIds) > 100) {
            return new JsonResponse([
                'error' => 'Too many residents (max 100)',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Call Application Service (bulk operation)
            $results = $this->attributionService->attributeGiftsInBulk($habitantIds);

            // Convert DTOs to array
            $resultsArray = array_map(
                fn(AttributionResultDTO $dto) => $dto->toArray(),
                $results
            );

            // Count successes and failures
            $successCount = count(array_filter($results, fn($r) => $r->success));
            $failureCount = count($results) - $successCount;

            return new JsonResponse([
                'success' => true,
                'summary' => [
                    'total' => count($results),
                    'succeeded' => $successCount,
                    'failed' => $failureCount,
                ],
                'results' => $resultsArray,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Bulk attribution failed', [
                'habitantIds' => $habitantIds,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Bulk attribution failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get attribution statistics.
     *
     * GET /api/attributions/stats
     *
     * Example using DTOs for read operations.
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        // In real app, would call QueryHandler that returns DTO
        // For demo, return mock data

        return new JsonResponse([
            'stats' => [
                'totalAttributions' => 150,
                'thisMonth' => 45,
                'pending' => 12,
                'byCategory' => [
                    'children' => 60,
                    'adults' => 70,
                    'seniors' => 20,
                ],
            ],
        ], Response::HTTP_OK);
    }
}
