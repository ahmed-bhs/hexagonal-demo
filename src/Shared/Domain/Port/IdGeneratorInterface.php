<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

/**
 * Port for generating unique identifiers.
 *
 * This is a PRIMARY PORT (outbound) in hexagonal architecture.
 * The Application layer depends on this interface, and the Infrastructure layer
 * provides the concrete implementation.
 *
 * Why this port exists:
 * - Keeps Application layer free from infrastructure dependencies
 * - Allows different ID generation strategies (UUID v4, v7, ULID, Snowflake, etc.)
 * - Enables deterministic testing with fake/mock implementations
 * - Follows Dependency Inversion Principle
 *
 * Example implementations:
 * - UuidV7Generator (time-ordered, database-friendly)
 * - UlidGenerator (lexicographically sortable)
 * - SnowflakeGenerator (distributed systems)
 * - FakeIdGenerator (testing with predictable IDs)
 */
interface IdGeneratorInterface
{
    /**
     * Generate a unique identifier.
     *
     * @return string The generated unique identifier in string format
     *                (e.g., "018c1e7e-9c4d-7b5a-8f2e-3d4c5b6a7890")
     */
    public function generate(): string;
}
