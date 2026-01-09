<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Cadeau\Attribution\Domain\Model\Habitant;
use App\Cadeau\Attribution\Domain\ValueObject\Age;
use App\Cadeau\Attribution\Domain\ValueObject\Email;
use App\Cadeau\Attribution\Domain\ValueObject\HabitantId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class HabitantFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $habitantsData = [
            ['prenom' => 'Marie', 'nom' => 'Dupont', 'age' => 8, 'email' => 'marie.dupont@example.com'],
            ['prenom' => 'Pierre', 'nom' => 'Martin', 'age' => 35, 'email' => 'pierre.martin@example.com'],
            ['prenom' => 'Sophie', 'nom' => 'Bernard', 'age' => 42, 'email' => 'sophie.bernard@example.com'],
            ['prenom' => 'Lucas', 'nom' => 'Dubois', 'age' => 12, 'email' => 'lucas.dubois@example.com'],
            ['prenom' => 'Jeanne', 'nom' => 'Petit', 'age' => 67, 'email' => 'jeanne.petit@example.com'],
            ['prenom' => 'Thomas', 'nom' => 'Roux', 'age' => 28, 'email' => 'thomas.roux@example.com'],
            ['prenom' => 'Emma', 'nom' => 'Leroy', 'age' => 15, 'email' => 'emma.leroy@example.com'],
            ['prenom' => 'Paul', 'nom' => 'Moreau', 'age' => 72, 'email' => 'paul.moreau@example.com'],
            ['prenom' => 'Camille', 'nom' => 'Simon', 'age' => 31, 'email' => 'camille.simon@example.com'],
            ['prenom' => 'Hugo', 'nom' => 'Laurent', 'age' => 19, 'email' => 'hugo.laurent@example.com'],
        ];

        foreach ($habitantsData as $data) {
            $habitant = new Habitant(
                new HabitantId(Uuid::v4()->toRfc4122()),
                $data['prenom'],
                $data['nom'],
                new Age($data['age']),
                new Email($data['email'])
            );

            $manager->persist($habitant);

            // Store reference for other fixtures
            $this->addReference('habitant_' . strtolower($data['prenom']), $habitant);
        }

        $manager->flush();
    }
}
