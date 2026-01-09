<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\UI\Http\Web\Controller;

use App\Cadeau\Demande\Application\SoumettreDemandeCadeau\SoumettreDemandeCadeauCommand;
use App\Cadeau\Demande\UI\Http\Web\Form\DemandeCadeauType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
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
#[Route('/demande-cadeau', name: 'app.cadeau.demande.form')]
final class DemandeCadeauFormController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(DemandeCadeauType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $command = new SoumettreDemandeCadeauCommand(
                nomDemandeur: $data['nomDemandeur'],
                emailDemandeur: $data['emailDemandeur'],
                telephoneDemandeur: $data['telephoneDemandeur'] ?? '',
                cadeauSouhaite: $data['cadeauSouhaite'],
                motivation: $data['motivation'],
            );

            try {
                $this->commandBus->dispatch($command);

                $this->addFlash('success', 'Votre demande a été soumise avec succès ! Nous vous contacterons prochainement.');
                return $this->redirectToRoute('app.cadeau.demande.form');

            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la soumission de votre demande.');
            }
        }

        return $this->render('cadeau/demande/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
