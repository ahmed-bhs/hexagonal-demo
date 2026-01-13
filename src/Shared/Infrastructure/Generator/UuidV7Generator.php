<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Generator;

use App\Shared\Domain\Port\IdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * UUID v7 Generator - Infrastructure Adapter.
 *
 * This is a SECONDARY ADAPTER (infrastructure implementation) in hexagonal architecture.
 * It implements the IdGeneratorInterface port defined in the Domain layer.
 *
 * Why UUID v7?
 * - Time-ordered: UUIDs are sortable by creation time
 * - Database-friendly: Better B-tree index performance (sequential inserts)
 * - Compatible: Still follows RFC 4122 standard
 * - Modern: Replaces UUID v4 for most use cases (since 2021)
 *
 * UUID v7 format:
 * - 48 bits: Unix timestamp (milliseconds)
 * - 12 bits: Random data for uniqueness within same millisecond
 * - 62 bits: Random data for global uniqueness
 *
 * Example UUID v7: 018c1e7e-9c4d-7b5a-8f2e-3d4c5b6a7890
 *                  └─────┘ timestamp portion (time-ordered)
 *
 * Advantages over UUID v4:
 * ✅ Better database performance (sequential keys)
 * ✅ Sortable by creation time
 * ✅ Less index fragmentation
 * ✅ Better for distributed systems
 *
 * @see https://www.rfc-editor.org/rfc/rfc9562.html UUID v7 specification
 */
final readonly class UuidV7Generator implements IdGeneratorInterface
{
    /**
     * Generate a time-ordered UUID v7.
     *
     * @return string UUID v7 in canonical string format (36 characters)
     *                Example: "018c1e7e-9c4d-7b5a-8f2e-3d4c5b6a7890"
     */
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
