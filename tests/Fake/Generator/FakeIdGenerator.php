<?php

declare(strict_types=1);

namespace App\Tests\Fake\Generator;

use App\Shared\Domain\Port\IdGeneratorInterface;

/**
 * Fake ID Generator for testing purposes.
 *
 * This is a TEST DOUBLE that implements both IdGeneratorInterface ports
 * to provide deterministic, predictable IDs for unit and integration tests.
 *
 * Benefits:
 * ✅ Deterministic IDs (no randomness)
 * ✅ Predictable sequences
 * ✅ Easy assertions in tests
 * ✅ No need to mock Symfony Uid
 *
 * Usage in tests:
 * ```php
 * $idGenerator = new FakeIdGenerator();
 *
 * $handler = new AttribuerCadeauxCommandHandler(
 *     $idGenerator,
 *     $habitantRepository,
 *     $cadeauRepository,
 *     $attributionRepository
 * );
 *
 * $handler->__invoke($command);
 *
 * $attribution = $attributionRepository->findById('fake-id-1');
 * $this->assertNotNull($attribution);  // ✅ Deterministic!
 * ```
 *
 * Custom ID example:
 * ```php
 * $idGenerator = new FakeIdGenerator();
 * $idGenerator->setNextId('my-custom-id');
 *
 * $id = $idGenerator->generate();
 * // $id === 'my-custom-id'
 * ```
 */
final class FakeIdGenerator implements IdGeneratorInterface
{
    private int $counter = 1;
    private ?string $nextId = null;

    /**
     * Generate a deterministic fake ID.
     *
     * If a custom ID was set via setNextId(), it will be returned once.
     * Otherwise, generates IDs in sequence: "fake-id-1", "fake-id-2", etc.
     *
     * @return string The generated fake ID
     */
    public function generate(): string
    {
        if ($this->nextId !== null) {
            $id = $this->nextId;
            $this->nextId = null;
            return $id;
        }

        return sprintf('fake-id-%d', $this->counter++);
    }

    /**
     * Set a custom ID to be returned on the next generate() call.
     *
     * Useful for specific test scenarios where you need a particular ID format.
     *
     * @param string $id The custom ID to return next
     * @return self For method chaining
     */
    public function setNextId(string $id): self
    {
        $this->nextId = $id;
        return $this;
    }

    /**
     * Reset the counter to start from 1 again.
     *
     * Useful when you need to reset the sequence between tests.
     *
     * @return self For method chaining
     */
    public function reset(): self
    {
        $this->counter = 1;
        $this->nextId = null;
        return $this;
    }

    /**
     * Get the current counter value.
     *
     * Useful for debugging or assertions.
     *
     * @return int Current counter value
     */
    public function getCounter(): int
    {
        return $this->counter;
    }
}
