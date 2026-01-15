<?php

declare(strict_types=1);

namespace App\Security\User\Domain\Port;

use App\Security\User\Domain\Model\User;
use App\Security\User\Domain\ValueObject\Email;
use App\Security\User\Domain\ValueObject\UserId;

/**
 * User Repository Port (Interface)
 *
 * Domain defines what it needs.
 * Infrastructure provides the implementation.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID
     */
    public function findById(UserId $id): ?User;

    /**
     * Find user by email
     */
    public function findByEmail(Email $email): ?User;

    /**
     * Check if email already exists
     */
    public function emailExists(Email $email): bool;

    /**
     * Save user (create or update)
     */
    public function save(User $user): void;
}
