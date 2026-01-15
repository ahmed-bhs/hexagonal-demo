<?php

declare(strict_types=1);

namespace App\Security\User\Infrastructure\Persistence\Doctrine;

use App\Security\User\Domain\Model\User;
use App\Security\User\Domain\Port\UserRepositoryInterface;
use App\Security\User\Domain\ValueObject\Email;
use App\Security\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine User Repository (Adapter)
 *
 * Implements UserRepositoryInterface port.
 */
final readonly class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findById(UserId $id): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['id.value' => $id->value()]);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email.value' => $email->value()]);
    }

    public function emailExists(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
