<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\DTO;

/**
 * DTO: Attribution Result.
 *
 * Data Transfer Object for transferring attribution results
 * from Application layer to UI layer (controllers, CLI).
 *
 * What is a DTO?
 * - Simple data container (no behavior)
 * - Transfers data between layers
 * - Can be serialized (JSON, XML)
 * - Immutable (readonly)
 *
 * DTO vs Domain Entity:
 * - DTO: Data transfer, serialization, UI concerns
 * - Entity: Business logic, invariants, identity
 *
 * DTO vs Value Object:
 * - DTO: Transport between layers
 * - VO: Domain concept with validation
 *
 * When to use DTO?
 * ✅ API responses (REST, GraphQL)
 * ✅ CLI command output
 * ✅ Inter-service communication
 * ✅ Complex query results (multiple aggregates)
 * ❌ Within same layer (use domain objects)
 * ❌ Simple CRUD (use domain entities directly)
 *
 * Benefits:
 * - Decouples UI from Domain
 * - Stable API contracts (domain can change)
 * - Optimized for serialization
 * - Can aggregate data from multiple sources
 */
final readonly class AttributionResultDTO
{
    public function __construct(
        public bool $success,
        public string $habitantId,
        public string $habitantName,
        public string $giftId,
        public string $giftName,
        public \DateTimeImmutable $attributedAt,
        public ?string $errorMessage = null
    ) {
    }

    /**
     * Create success DTO.
     */
    public static function success(
        string $habitantId,
        string $habitantName,
        string $giftId,
        string $giftName,
        \DateTimeImmutable $attributedAt
    ): self {
        return new self(
            success: true,
            habitantId: $habitantId,
            habitantName: $habitantName,
            giftId: $giftId,
            giftName: $giftName,
            attributedAt: $attributedAt
        );
    }

    /**
     * Create failure DTO.
     */
    public static function failure(
        string $habitantId,
        string $reason,
        ?string $habitantName = null,
        ?string $giftId = null,
        ?string $giftName = null
    ): self {
        return new self(
            success: false,
            habitantId: $habitantId,
            habitantName: $habitantName ?? 'Unknown',
            giftId: $giftId ?? '',
            giftName: $giftName ?? '',
            attributedAt: new \DateTimeImmutable(),
            errorMessage: $reason
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
            'success' => $this->success,
            'habitant' => [
                'id' => $this->habitantId,
                'name' => $this->habitantName,
            ],
            'gift' => [
                'id' => $this->giftId,
                'name' => $this->giftName,
            ],
            'attributedAt' => $this->attributedAt->format(\DateTimeInterface::ATOM),
            'error' => $this->errorMessage,
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
