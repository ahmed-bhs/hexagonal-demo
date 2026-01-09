<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\UI\Http\Web\Controller;

use App\Cadeau\Attribution\Application\RecupererCadeaux\RecupererCadeauxQuery;
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
