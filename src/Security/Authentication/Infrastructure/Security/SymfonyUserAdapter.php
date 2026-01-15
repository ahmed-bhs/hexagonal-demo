<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\Security;

use App\Security\User\Domain\Model\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Symfony User Adapter
 *
 * Adapts Domain User to Symfony UserInterface.
 * This allows Symfony Security to work with our domain User.
 */
final readonly class SymfonyUserAdapter implements UserInterface
{
    public function __construct(
        private User $user,
    ) {}

    public function getRoles(): array
    {
        // Ensure ROLE_USER is always present
        $roles = $this->user->roles();
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase (password is in domain object)
    }

    public function getUserIdentifier(): string
    {
        return $this->user->id()->value();
    }

    /**
     * Get the wrapped domain User
     */
    public function getDomainUser(): User
    {
        return $this->user;
    }
}
