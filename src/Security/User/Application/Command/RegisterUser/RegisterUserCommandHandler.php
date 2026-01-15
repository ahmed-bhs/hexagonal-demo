<?php

declare(strict_types=1);

namespace App\Security\User\Application\Command\RegisterUser;

use App\Security\User\Application\Exception\EmailAlreadyExistsException;
use App\Security\User\Domain\Model\User;
use App\Security\User\Domain\Port\PasswordHasherInterface;
use App\Security\User\Domain\Port\UserRepositoryInterface;
use App\Security\User\Domain\ValueObject\Email;
use App\Security\User\Domain\ValueObject\HashedPassword;
use App\Security\User\Domain\ValueObject\UserId;
use App\Shared\Domain\Port\IdGeneratorInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Command Handler: Register User
 *
 * Orchestrates user registration use case.
 * Domain logic is in User aggregate.
 */
#[AsMessageHandler]
final readonly class RegisterUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private IdGeneratorInterface $idGenerator,
    ) {}

    /**
     * @throws EmailAlreadyExistsException
     */
    public function __invoke(RegisterUserCommand $command): UserId
    {
        // Create Value Objects
        $email = new Email($command->email);

        // Business Rule: Email must be unique
        if ($this->userRepository->emailExists($email)) {
            throw new EmailAlreadyExistsException($email->value());
        }

        // Hash password (via port)
        $hashedPassword = new HashedPassword(
            $this->passwordHasher->hash($command->plainPassword)
        );

        // Generate ID (via port)
        $userId = new UserId($this->idGenerator->generate());

        // Create User aggregate (Domain logic)
        $user = User::register(
            id: $userId,
            email: $email,
            password: $hashedPassword,
            roles: $command->roles,
        );

        // Persist
        $this->userRepository->save($user);

        // Events are automatically published by DomainEventPublisherListener
        // after successful flush

        return $userId;
    }
}
