<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\DTO;

use App\Cadeau\Attribution\Domain\Model\Habitant;

/**
 * DTO: Habitant (Resident) Information.
 *
 * Transfers resident data from Domain to UI layer.
 * Includes computed/presentation fields not in Domain.
 *
 * Computed fields examples:
 * - fullName (from firstName + lastName)
 * - ageCategory (from age)
 * - displayEmail (formatted/masked)
 *
 * This demonstrates how DTOs can add presentation logic
 * without polluting the Domain layer.
 */
final readonly class HabitantDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public int $age,
        public string $ageCategory,
        public string $email,
        public bool $isAdult,
        public bool $isSenior,
        public bool $isChild
    ) {
    }

    /**
     * Create DTO from Domain Entity.
     */
    public static function fromEntity(Habitant $habitant): self
    {
        $age = $habitant->getAge();
        $firstName = $habitant->getPrenom();
        $lastName = $habitant->getNom();

        // Presentation logic: determine age category label
        $ageCategory = match (true) {
            $age->isChild() => 'Child (< 18)',
            $age->isSenior() => 'Senior (≥ 65)',
            default => 'Adult (18-64)',
        };

        return new self(
            id: $habitant->getId()->value,
            firstName: $firstName,
            lastName: $lastName,
            fullName: $firstName . ' ' . $lastName,
            age: $age->value,
            ageCategory: $ageCategory,
            email: $habitant->getEmail()->value,
            isAdult: $age->isAdult(),
            isSenior: $age->isSenior(),
            isChild: $age->isChild()
        );
    }

    /**
     * Create a version with masked email for privacy.
     *
     * Example: john.doe@example.com → j***@example.com
     */
    public function withMaskedEmail(): self
    {
        $parts = explode('@', $this->email);
        if (count($parts) !== 2) {
            $maskedEmail = '***';
        } else {
            $username = $parts[0];
            $domain = $parts[1];
            $maskedUsername = substr($username, 0, 1) . '***';
            $maskedEmail = $maskedUsername . '@' . $domain;
        }

        return new self(
            id: $this->id,
            firstName: $this->firstName,
            lastName: $this->lastName,
            fullName: $this->fullName,
            age: $this->age,
            ageCategory: $this->ageCategory,
            email: $maskedEmail,
            isAdult: $this->isAdult,
            isSenior: $this->isSenior,
            isChild: $this->isChild
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $this->fullName,
            'age' => $this->age,
            'ageCategory' => $this->ageCategory,
            'email' => $this->email,
            'flags' => [
                'isAdult' => $this->isAdult,
                'isSenior' => $this->isSenior,
                'isChild' => $this->isChild,
            ],
        ];
    }

    /**
     * Implements JsonSerializable.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create compact version for list views.
     *
     * Useful when you need minimal data in a list.
     *
     * @return array<string, string|int>
     */
    public function toCompact(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->fullName,
            'age' => $this->age,
        ];
    }
}
