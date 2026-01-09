<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Cadeau\Attribution\Domain\Model\Cadeau;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class CadeauFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cadeauxData = [
            [
                'nom' => 'Livre Harry Potter',
                'description' => 'Coffret complet de la saga Harry Potter en édition illustrée',
                'quantite' => 15
            ],
            [
                'nom' => 'Lego Architecture',
                'description' => 'Set de construction Lego de monuments célèbres',
                'quantite' => 8
            ],
            [
                'nom' => 'Console Nintendo Switch',
                'description' => 'Console de jeu portable dernière génération avec 2 Joy-Con',
                'quantite' => 3
            ],
            [
                'nom' => 'Vélo Électrique',
                'description' => 'Vélo à assistance électrique avec autonomie de 80km',
                'quantite' => 2
            ],
            [
                'nom' => 'Robot Cuiseur',
                'description' => 'Robot multifonction pour cuisiner facilement',
                'quantite' => 5
            ],
            [
                'nom' => 'Montre Connectée',
                'description' => 'Smartwatch avec suivi d\'activité et notifications',
                'quantite' => 12
            ],
            [
                'nom' => 'Kit de Peinture',
                'description' => 'Ensemble complet de peinture acrylique avec chevalet',
                'quantite' => 20
            ],
            [
                'nom' => 'Drone avec Caméra',
                'description' => 'Drone HD avec stabilisation d\'image',
                'quantite' => 0
            ],
            [
                'nom' => 'Coffret Cosmétiques',
                'description' => 'Set de produits de beauté premium',
                'quantite' => 25
            ],
            [
                'nom' => 'Tablette Graphique',
                'description' => 'Tablette numérique pour dessin digital',
                'quantite' => 6
            ],
        ];

        foreach ($cadeauxData as $data) {
            $cadeau = Cadeau::create(
                Uuid::v4()->toRfc4122(),
                $data['nom'],
                $data['description'],
                $data['quantite']
            );

            $manager->persist($cadeau);

            // Store reference (normalize accents and lowercase)
            $ref = mb_strtolower(str_replace(' ', '_', $data['nom']), 'UTF-8');
            $this->addReference('cadeau_' . $ref, $cadeau);
        }

        $manager->flush();
    }
}
