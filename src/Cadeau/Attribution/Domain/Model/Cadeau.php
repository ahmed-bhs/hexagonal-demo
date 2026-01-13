<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Domain\Model;

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
 * Infrastructure/Persistence/Doctrine/Orm/Mapping/Cadeau.orm.yml
 */
class Cadeau
{
    private string $id;
    private string $nom;
    private string $description;
    private int $quantite;

    private function __construct(
        string $id,
        string $nom,
        string $description,
        int $quantite,
    ) {
        $this->id = $id;

        // Domain validation
        if (empty(trim($nom))) {
            throw new \InvalidArgumentException('nom cannot be empty');
        }

        if (strlen(trim($nom)) < 3) {
            throw new \InvalidArgumentException('nom must be at least 3 characters');
        }

        if (strlen(trim($nom)) > 100) {
            throw new \InvalidArgumentException('nom cannot exceed 100 characters');
        }

        if ($quantite < 0) {
            throw new \InvalidArgumentException('quantite cannot be negative');
        }

        if ($quantite > 1000) {
            throw new \InvalidArgumentException('quantite cannot exceed 1000');
        }
        // Initialize properties
        $this->nom = trim($nom);
        $this->description = trim($description);
        $this->quantite = $quantite;
    }

    public static function create(
        string $id,
        string $nom,
        string $description,
        int $quantite
    ): self {
        return new self(
            $id,
            $nom,
            $description,
            $quantite
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    // Getters

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    // Business logic methods

    public function isEnStock(): bool
    {
        return $this->quantite > 0;
    }

    public function estDisponible(int $quantiteDemandee): bool
    {
        return $this->quantite >= $quantiteDemandee;
    }
}
