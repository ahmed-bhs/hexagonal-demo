<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Cadeau\Attribution\Domain\Model\Attribution;
use App\Cadeau\Attribution\Domain\Model\Habitant;
use App\Cadeau\Attribution\Domain\Model\Cadeau;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class AttributionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Marie (enfant) → Livre Harry Potter
        $this->createAttribution(
            $manager,
            'habitant_marie',
            'cadeau_livre_harry_potter'
        );

        // Lucas (enfant) → Lego Architecture
        $this->createAttribution(
            $manager,
            'habitant_lucas',
            'cadeau_lego_architecture'
        );

        // Pierre (adulte) → Vélo Électrique
        $this->createAttribution(
            $manager,
            'habitant_pierre',
            'cadeau_vélo_électrique'
        );

        // Sophie (adulte) → Robot Cuiseur
        $this->createAttribution(
            $manager,
            'habitant_sophie',
            'cadeau_robot_cuiseur'
        );

        // Jeanne (senior) → Montre Connectée
        $this->createAttribution(
            $manager,
            'habitant_jeanne',
            'cadeau_montre_connectée'
        );

        // Thomas (adulte) → Console Nintendo Switch
        $this->createAttribution(
            $manager,
            'habitant_thomas',
            'cadeau_console_nintendo_switch'
        );

        // Emma (adolescente) → Tablette Graphique
        $this->createAttribution(
            $manager,
            'habitant_emma',
            'cadeau_tablette_graphique'
        );

        $manager->flush();
    }

    private function createAttribution(
        ObjectManager $manager,
        string $habitantRef,
        string $cadeauRef
    ): void {
        /** @var Habitant $habitant */
        $habitant = $this->getReference($habitantRef, Habitant::class);

        /** @var Cadeau $cadeau */
        $cadeau = $this->getReference($cadeauRef, Cadeau::class);

        $attribution = Attribution::create(
            Uuid::v4()->toRfc4122(),
            $habitant->getId()->value,
            $cadeau->getId()
        );

        $manager->persist($attribution);
    }

    public function getDependencies(): array
    {
        return [
            HabitantFixtures::class,
            CadeauFixtures::class,
        ];
    }
}
