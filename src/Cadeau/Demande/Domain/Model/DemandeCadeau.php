<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Domain\Model;

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
 * Infrastructure/Persistence/Doctrine/Orm/Mapping/DemandeCadeau.orm.yml
 *
 * Note: Not final to allow Doctrine lazy loading with ghost objects (ORM 3.x)
 */
class DemandeCadeau
{
    private string $id;
    private string $nomDemandeur;
    private string $emailDemandeur;
    private string $telephoneDemandeur;
    private string $cadeauSouhaite;
    private string $motivation;
    private string $statut;
    private \DateTimeImmutable $dateCreation;

    private function __construct(
        string $id,
        string $nomDemandeur,
        string $emailDemandeur,
        string $telephoneDemandeur,
        string $cadeauSouhaite,
        string $motivation,
    ) {
        $this->id = $id;
        $this->nomDemandeur = $nomDemandeur;
        $this->emailDemandeur = $emailDemandeur;
        $this->telephoneDemandeur = $telephoneDemandeur;
        $this->cadeauSouhaite = $cadeauSouhaite;
        $this->motivation = $motivation;
        $this->statut = 'en_attente';
        $this->dateCreation = new \DateTimeImmutable();
    }

    public static function create(
        string $id,
        string $nomDemandeur,
        string $emailDemandeur,
        string $telephoneDemandeur,
        string $cadeauSouhaite,
        string $motivation,
    ): self {
        if (empty($nomDemandeur)) {
            throw new \InvalidArgumentException('Le nom du demandeur ne peut pas être vide');
        }
        if (!filter_var($emailDemandeur, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }
        if (empty($cadeauSouhaite)) {
            throw new \InvalidArgumentException('Le cadeau souhaité ne peut pas être vide');
        }
        if (empty($motivation)) {
            throw new \InvalidArgumentException('La motivation ne peut pas être vide');
        }

        return new self($id, $nomDemandeur, $emailDemandeur, $telephoneDemandeur, $cadeauSouhaite, $motivation);
    }

    public static function reconstitute(
        string $id,
        string $nomDemandeur,
        string $emailDemandeur,
        string $telephoneDemandeur,
        string $cadeauSouhaite,
        string $motivation,
        string $statut,
        \DateTimeImmutable $dateCreation,
    ): self {
        $demande = new self($id, $nomDemandeur, $emailDemandeur, $telephoneDemandeur, $cadeauSouhaite, $motivation);
        $demande->statut = $statut;
        $demande->dateCreation = $dateCreation;
        return $demande;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNomDemandeur(): string
    {
        return $this->nomDemandeur;
    }

    public function getEmailDemandeur(): string
    {
        return $this->emailDemandeur;
    }

    public function getTelephoneDemandeur(): string
    {
        return $this->telephoneDemandeur;
    }

    public function getCadeauSouhaite(): string
    {
        return $this->cadeauSouhaite;
    }

    public function getMotivation(): string
    {
        return $this->motivation;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function approuver(): void
    {
        if ($this->statut === 'approuvee') {
            throw new \DomainException('La demande est déjà approuvée');
        }
        $this->statut = 'approuvee';
    }

    public function rejeter(): void
    {
        if ($this->statut === 'rejetee') {
            throw new \DomainException('La demande est déjà rejetée');
        }
        $this->statut = 'rejetee';
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }
}
