<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\UI\Http\Web\Controller;

use App\Cadeau\Attribution\Application\RecupererHabitants\RecupererHabitantsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

/**
 * UI Layer - Web Controller.
 *
 * Part of the UI (User Interface) layer in hexagonal architecture.
 * Responsible for:
 * - Handling HTTP requests
 * - Validating input
 * - Calling use cases/command handlers
 * - Rendering responses (templates, JSON, etc.)
 *
 * This controller is a PRIMARY ADAPTER (driving adapter) that drives the application core.
 */
#[Route('/habitants', name: 'app.cadeau.attribution.list_habitants')]
final class ListHabitantsController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = min(100, max(1, $request->query->getInt('per_page', 10)));
        $searchTerm = $request->query->getString('search', '');

        $errors = [];
        $response = null;

        try {
            // Execute query to retrieve habitants with pagination
            $query = new RecupererHabitantsQuery(
                page: $page,
                perPage: $perPage,
                searchTerm: $searchTerm
            );
            $envelope = $this->queryBus->dispatch($query);

            // Get response from handler
            $handledStamp = $envelope->last(HandledStamp::class);
            $response = $handledStamp->getResult();
        } catch (HandlerFailedException $e) {
            // Symfony Messenger wraps exceptions in HandlerFailedException
            // Extract the original exception
            $originalException = $e->getPrevious();

            if ($originalException instanceof \InvalidArgumentException) {
                // Capture validation errors from ValueObjects
                $errors[] = $originalException->getMessage();
            } else {
                // Capture any other errors
                $errors[] = 'Une erreur est survenue lors de la récupération des habitants.';
            }
        } catch (\InvalidArgumentException $e) {
            // Direct validation errors (if any)
            $errors[] = $e->getMessage();
        } catch (\Exception $e) {
            // Capture any other errors
            $errors[] = 'Une erreur est survenue lors de la récupération des habitants.';
        }

        // Render template with habitants data
        return $this->render('cadeau/attribution/list_habitants.html.twig', [
            'response' => $response,
            'searchTerm' => $searchTerm,
            'errors' => $errors,
        ]);
    }
}
