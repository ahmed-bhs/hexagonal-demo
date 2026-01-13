<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Model;

use App\Cadeau\Attribution\Domain\ValueObject\Age;
use App\Shared\Domain\ValueObject\Email;
use App\Cadeau\Attribution\Domain\ValueObject\HabitantId;

/**
 * Domain Entity.
 *
 * Represents a domain concept with identity and lifecycle.
 * Contains business logic and enforces invariants.
 *
 * In hexagonal architecture, entities are part of the Domain layer (core)
 * and are completely independent of infrastructure concerns.
 *
 * ⚠️ IMPORTANT: This entity is PURE - no framework dependencies.
 * Doctrine ORM mapping is configured separately in:
 * Infrastructure/Persistence/Doctrine/Orm/Mapping/Habitant.orm.yml
 */
class Habitant
{
    private HabitantId $id;
    private string $prenom;
    private string $nom;
    private Age $age;
    private Email $email;

    public function __construct(
        HabitantId $id,
        string $prenom,
        string $nom,
        Age $age,
        Email $email
    ) {
        if (empty(trim($prenom))) {
            throw new \InvalidArgumentException('Le prénom ne peut pas être vide');
        }

        if (empty(trim($nom))) {
            throw new \InvalidArgumentException('Le nom ne peut pas être vide');
        }

        $this->id = $id;
        $this->prenom = trim($prenom);
        $this->nom = trim($nom);
        $this->age = $age;
        $this->email = $email;
    }

    public static function create(
        HabitantId $id,
        string $prenom,
        string $nom,
        Age $age,
        Email $email
    ): self {
        return new self(
            $id,
            $prenom,
            $nom,
            $age,
            $email
        );
    }

    public function getId(): HabitantId
    {
        return $this->id;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getAge(): Age
    {
        return $this->age;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function isAdulte(): bool
    {
        return $this->age->isAdult();
    }

    public function isSenior(): bool
    {
        return $this->age->isSenior();
    }

    public function isEnfant(): bool
    {
        return $this->age->isChild();
    }
}
