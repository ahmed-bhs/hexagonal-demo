<?php

declare(strict_types=1);

namespace App\Shared\Pagination\Domain\ValueObject;

/**
 * Domain Value Object.
 *
 * Represents a domain concept defined by its attributes rather than identity.
 * Value objects are immutable and can be compared by value.
 *
 * In hexagonal architecture, value objects are part of the Domain layer
 * and help enforce domain invariants and encapsulate business rules.
 */
final readonly class PaginatedResult
{
    /**
     * @param array<int, mixed> $items
     */
    public function __construct(
        public array $items,
        public Page $currentPage,
        public PerPage $perPage,
        public Total $total,
    ) {
    }

    public function getTotalPages(): int
    {
        if ($this->total->isEmpty()) {
            return 1;
        }

        return (int) ceil($this->total->toInt() / $this->perPage->toInt());
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage->toInt() < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage->toInt() > 1;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
