<?php

declare(strict_types=1);

namespace App\Cadeau\Demande\Application\DTO;

use App\Cadeau\Demande\Domain\Model\DemandeCadeau;

/**
 * DTO: Gift Request Summary.
 *
 * Example of a DTO that aggregates data from multiple sources
 * or adds complex presentation logic.
 *
 * Use case: Display gift request summary in a dashboard
 * with computed fields like status label, days pending, etc.
 */
final readonly class GiftRequestSummaryDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $requesterName,
        public string $requesterEmail,
        public string $requestedGift,
        public string $motivation,
        public string $status,
        public string $statusLabel,
        public string $statusColor,
        public \DateTimeImmutable $createdAt,
        public int $daysPending,
        public bool $isPending,
        public bool $isApproved,
        public bool $isRejected
    ) {
    }

    /**
     * Create DTO from Domain Entity with presentation logic.
     */
    public static function fromEntity(DemandeCadeau $demandeCadeau): self
    {
        $status = $demandeCadeau->getStatut();

        // Presentation logic: status labels and colors for UI
        [$statusLabel, $statusColor] = match ($status) {
            'en_attente' => ['Pending Review', 'warning'],
            'approuvee' => ['Approved', 'success'],
            'rejetee' => ['Rejected', 'danger'],
            default => ['Unknown', 'secondary'],
        };

        // Compute days pending
        $now = new \DateTimeImmutable();
        $daysPending = $now->diff($demandeCadeau->getDateCreation())->days;

        return new self(
            id: $demandeCadeau->getId(),
            requesterName: $demandeCadeau->getNomDemandeur(),
            requesterEmail: $demandeCadeau->getEmailDemandeur()->value,
            requestedGift: $demandeCadeau->getCadeauSouhaite(),
            motivation: $demandeCadeau->getMotivation(),
            status: $status,
            statusLabel: $statusLabel,
            statusColor: $statusColor,
            createdAt: $demandeCadeau->getDateCreation(),
            daysPending: $daysPending,
            isPending: $demandeCadeau->estEnAttente(),
            isApproved: $status === 'approuvee',
            isRejected: $status === 'rejetee'
        );
    }

    /**
     * Check if request is urgent (pending for more than 7 days).
     */
    public function isUrgent(): bool
    {
        return $this->isPending && $this->daysPending > 7;
    }

    /**
     * Get urgency level.
     */
    public function getUrgencyLevel(): string
    {
        if (!$this->isPending) {
            return 'none';
        }

        return match (true) {
            $this->daysPending > 14 => 'critical',
            $this->daysPending > 7 => 'high',
            $this->daysPending > 3 => 'medium',
            default => 'low',
        };
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
            'requester' => [
                'name' => $this->requesterName,
                'email' => $this->requesterEmail,
            ],
            'request' => [
                'gift' => $this->requestedGift,
                'motivation' => $this->motivation,
            ],
            'status' => [
                'code' => $this->status,
                'label' => $this->statusLabel,
                'color' => $this->statusColor,
                'isPending' => $this->isPending,
                'isApproved' => $this->isApproved,
                'isRejected' => $this->isRejected,
            ],
            'timing' => [
                'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
                'daysPending' => $this->daysPending,
                'isUrgent' => $this->isUrgent(),
                'urgencyLevel' => $this->getUrgencyLevel(),
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
}
