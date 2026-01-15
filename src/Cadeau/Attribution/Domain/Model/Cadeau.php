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

    /**
     * Business Rule: A gift can be attributed if stock is available.
     *
     * ✅ SINGLE SOURCE OF TRUTH for this business rule.
     * This method is called by:
     * - CadeauDisponibleValidator (Infrastructure - preliminary validation)
     * - AttribuerCadeauCommandHandler (Application - final validation)
     *
     * @return bool True if the gift can be attributed (stock > 0)
     */
    public function peutEtreAttribue(): bool
    {
        return $this->quantite > 0;
    }

    /**
     * Business Rule: Decrease stock atomically with validation.
     *
     * ✅ ATOMIC OPERATION: Validates and decreases stock in one method.
     * This protects against race conditions when called inside a transaction.
     *
     * Flow:
     * 1. Check if gift is available (peutEtreAttribue)
     * 2. If not -> throw exception (rollback transaction)
     * 3. If yes -> decrease stock
     *
     * @throws \DomainException If stock is insufficient
     */
    public function diminuerStock(): void
    {
        if (!$this->peutEtreAttribue()) {
            throw new \DomainException(
                sprintf('Cannot attribute gift "%s" - out of stock', $this->nom)
            );
        }

        $this->quantite--;
    }
}
