<?php

declare(strict_types=1);

namespace App\Controller;

use App\Cadeau\Attribution\Application\RecupererStatistiques\RecupererStatistiquesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Home Controller - UI Layer.
 *
 * ✅ HEXAGONAL ARCHITECTURE:
 * This controller follows hexagonal principles:
 * - Depends on Application layer (Query)
 * - Uses MessageBus for CQRS pattern
 * - No direct access to repositories
 * - No knowledge of infrastructure details
 */
#[Route('/', name: 'app.home')]
final class HomeController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(): Response
    {
        // Execute query to retrieve statistics
        $query = new RecupererStatistiquesQuery();
        $envelope = $this->queryBus->dispatch($query);

        // Get response from handler
        $handledStamp = $envelope->last(HandledStamp::class);
        $response = $handledStamp->getResult();

        return $this->render('home/index.html.twig', [
            'stats' => $response,
        ]);
    }
}
