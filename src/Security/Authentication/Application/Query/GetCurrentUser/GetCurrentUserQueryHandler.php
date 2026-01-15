<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Query\GetCurrentUser;

use App\Security\User\Application\DTO\UserDTO;
use App\Security\User\Domain\Port\UserRepositoryInterface;
use App\Security\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Query Handler: Get Current User
 */
#[AsMessageHandler]
final readonly class GetCurrentUserQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(GetCurrentUserQuery $query): ?UserDTO
    {
        $user = $this->userRepository->findById(
            new UserId($query->userId)
        );

        if (!$user) {
            return null;
        }

        return UserDTO::fromEntity($user);
    }
}
