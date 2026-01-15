<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\Application\DTO;

use App\Cadeau\Attribution\Domain\Model\Cadeau;

/**
 * DTO: Gift Information.
 *
 * Transfers gift data from Domain to UI layer.
 * This DTO is optimized for display purposes.
 *
 * Why use DTO instead of Domain Entity directly?
 * 1. Decoupling: UI doesn't depend on Domain structure
 * 2. Serialization: Optimized for JSON/API responses
 * 3. Presentation: Can add computed fields (isLowStock)
 * 4. Security: Don't expose internal Domain details
 * 5. Stability: Domain can change without breaking API
 *
 * Example usage:
 * ```php
 * // In QueryHandler
 * public function __invoke(RecupererCadeauxQuery $query): RecupererCadeauxResponse
 * {
 *     $cadeaux = $this->repository->findAll();
 *
 *     $dtos = array_map(
 *         fn(Cadeau $cadeau) => GiftDTO::fromEntity($cadeau),
 *         $cadeaux
 *     );
 *
 *     return new RecupererCadeauxResponse($dtos);
 * }
 *
 * // In Controller
 * return new JsonResponse($response->gifts);
 * ```
 */
final readonly class GiftDTO implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $stock,
        public bool $isAvailable,
        public bool $isLowStock,
        public string $stockStatus
    ) {
    }

    /**
     * Create DTO from Domain Entity.
     *
     * This is the PRIMARY way to create a GiftDTO.
     * It ensures consistency with Domain rules.
     */
    public static function fromEntity(Cadeau $cadeau): self
    {
        $stock = $cadeau->getQuantite();
        $isAvailable = $stock > 0;
        $isLowStock = $stock > 0 && $stock <= 5;

        // Presentation logic: stock status message
        $stockStatus = match (true) {
            $stock === 0 => 'Out of stock',
            $stock <= 5 => 'Low stock',
            $stock <= 20 => 'In stock',
            default => 'Plenty in stock',
        };

        return new self(
            id: $cadeau->getId(),
            name: $cadeau->getNom(),
            description: $cadeau->getDescription(),
            stock: $stock,
            isAvailable: $isAvailable,
            isLowStock: $isLowStock,
            stockStatus: $stockStatus
        );
    }

    /**
     * Create DTO from raw data (for testing or external sources).
     */
    public static function fromArray(array $data): self
    {
        $stock = $data['stock'] ?? 0;

        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            stock: $stock,
            isAvailable: $stock > 0,
            isLowStock: $stock > 0 && $stock <= 5,
            stockStatus: $data['stockStatus'] ?? 'Unknown'
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
            'name' => $this->name,
            'description' => $this->description,
            'stock' => $this->stock,
            'isAvailable' => $this->isAvailable,
            'isLowStock' => $this->isLowStock,
            'stockStatus' => $this->stockStatus,
        ];
    }

    /**
     * Implements JsonSerializable.
     *
     * Allows: json_encode($giftDTO)
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
