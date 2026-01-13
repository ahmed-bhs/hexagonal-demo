<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\SoumettreDemandeCadeau;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQRS Command.
 *
 * Represents an intention to perform a write operation.
 * Commands should be immutable and contain all the data needed to execute the action.
 *
 * Validation Symfony (contraintes) :
 * - NotBlank : champ obligatoire
 * - Email : format email valide
 * - Length : longueur min/max
 * - Regex : pattern téléphone
 */
final readonly class SoumettreDemandeCadeauCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du demandeur ne peut pas être vide')]
        #[Assert\Length(
            min: 2,
            max: 100,
            minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
            maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
        )]
        public string $nomDemandeur,

        #[Assert\NotBlank(message: 'L\'email ne peut pas être vide')]
        #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide')]
        public string $emailDemandeur,

        #[Assert\NotBlank(message: 'Le téléphone ne peut pas être vide')]
        #[Assert\Regex(
            pattern: '/^(\+33|0)[1-9](\d{2}){4}$/',
            message: 'Le téléphone doit être au format français valide'
        )]
        public string $telephoneDemandeur,

        #[Assert\NotBlank(message: 'Le cadeau souhaité ne peut pas être vide')]
        #[Assert\Length(
            min: 3,
            max: 200,
            minMessage: 'Le cadeau souhaité doit contenir au moins {{ limit }} caractères',
            maxMessage: 'Le cadeau souhaité ne peut pas dépasser {{ limit }} caractères'
        )]
        public string $cadeauSouhaite,

        #[Assert\NotBlank(message: 'La motivation ne peut pas être vide')]
        #[Assert\Length(
            min: 10,
            max: 1000,
            minMessage: 'La motivation doit contenir au moins {{ limit }} caractères',
            maxMessage: 'La motivation ne peut pas dépasser {{ limit }} caractères'
        )]
        public string $motivation,
    ) {
    }
}
