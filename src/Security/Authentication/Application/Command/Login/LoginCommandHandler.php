<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Command\Login;

use App\Security\Authentication\Application\DTO\TokenDTO;
use App\Security\Authentication\Application\Exception\InvalidCredentialsException;
use App\Security\Authentication\Domain\Port\TokenGeneratorInterface;
use App\Security\User\Domain\Port\PasswordHasherInterface;
use App\Security\User\Domain\Port\UserRepositoryInterface;
use App\Security\User\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Command Handler: Login
 *
 * Authenticates user and generates JWT token.
 */
#[AsMessageHandler]
final readonly class LoginCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private TokenGeneratorInterface $tokenGenerator,
    ) {}

    /**
     * @throws InvalidCredentialsException
     */
    public function __invoke(LoginCommand $command): TokenDTO
    {
        // Find user by email
        $email = new Email($command->email);
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new InvalidCredentialsException();
        }

        // Verify password
        if (!$user->verifyPassword($command->plainPassword, $this->passwordHasher)) {
            throw new InvalidCredentialsException();
        }

        // Record login
        $user->recordLogin();
        $this->userRepository->save($user);

        // Generate JWT token
        $token = $this->tokenGenerator->generateToken($user);

        return new TokenDTO(
            token: $token,
            userId: $user->id()->value(),
            email: $user->email()->value(),
            roles: $user->roles(),
        );
    }
}
