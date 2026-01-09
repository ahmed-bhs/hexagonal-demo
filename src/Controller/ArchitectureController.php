<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to display architecture visualization.
 */
#[Route('/architecture', name: 'app.architecture')]
final class ArchitectureController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('architecture/index.html.twig');
    }
}
