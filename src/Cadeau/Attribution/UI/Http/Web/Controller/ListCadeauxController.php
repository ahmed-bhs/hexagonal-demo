<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\UI\Http\Web\Controller;

use App\Cadeau\Attribution\Application\Query\RecupererCadeaux\RecupererCadeauxQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to display the list of cadeaux.
 *
 * ✅ HEXAGONAL ARCHITECTURE:
 * This controller follows hexagonal principles:
 * - Depends on Application layer (Query)
 * - Uses MessageBus for CQRS pattern
 * - No direct access to repositories
 * - No knowledge of infrastructure details
 *
 * ALTERNATIVE APPROACH (API-FIRST with MapRequestPayload):
 * For REST API endpoints with empty queries, you can still use the same pattern.
 *
 * Example with API approach:
 *
 * #[Route('/api/cadeaux', name: 'api.cadeau.attribution.list_cadeaux', methods: ['GET'])]
 * public function __invoke(): Response {
 *     try {
 *         $query = new RecupererCadeauxQuery();
 *         $envelope = $this->queryBus->dispatch($query);
 *         $handledStamp = $envelope->last(HandledStamp::class);
 *         $response = $handledStamp->getResult();
 *
 *         return $this->json([
 *             'cadeaux' => array_map(
 *                 fn($cadeau) => [
 *                     'id' => $cadeau->getId(),
 *                     'nom' => $cadeau->getNom(),
 *                     'description' => $cadeau->getDescription(),
 *                     'quantite' => $cadeau->getQuantite(),
 *                     'enStock' => $cadeau->isEnStock(),
 *                 ],
 *                 $response->cadeaux
 *             )
 *         ]);
 *
 *     } catch (\Exception $e) {
 *         return $this->json([
 *             'status' => 'error',
 *             'message' => 'Erreur lors de la récupération des cadeaux'
 *         ], Response::HTTP_INTERNAL_SERVER_ERROR);
 *     }
 * }
 *
 * Example HTTP Request:
 * GET /api/cadeaux
 *
 * Response:
 * {
 *   "cadeaux": [
 *     {
 *       "id": "123e4567-e89b-12d3-a456-426614174000",
 *       "nom": "Vélo",
 *       "description": "Vélo de ville",
 *       "quantite": 5,
 *       "enStock": true
 *     }
 *   ]
 * }
 */
#[Route('/cadeaux', name: 'app.cadeau.attribution.list_cadeaux')]
final class ListCadeauxController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(): Response
    {
        // Execute query to retrieve all cadeaux
        $query = new RecupererCadeauxQuery();
        $envelope = $this->queryBus->dispatch($query);

        // Get response from handler
        $handledStamp = $envelope->last(HandledStamp::class);
        $response = $handledStamp->getResult();

        // Render template
        return $this->render('cadeau/attribution/list_cadeaux.html.twig', [
            'cadeaux' => $response->cadeaux,
        ]);
    }
}
