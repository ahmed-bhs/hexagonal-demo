<?php

declare(strict_types=1);

namespace App\Security\Authentication\UI\Http\Controller;

use App\Security\Authentication\Application\Command\Login\LoginCommand;
use App\Security\Authentication\Application\Exception\InvalidCredentialsException;
use App\Security\Authentication\Application\Query\GetCurrentUser\GetCurrentUserQuery;
use App\Security\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\Security\User\Application\Exception\EmailAlreadyExistsException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Authentication Controller
 *
 * Thin controller that dispatches Commands/Queries to Application layer.
 * All business logic is in Application/Domain layers.
 */
#[Route('/api/auth', name: 'api_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Register new user
     *
     * POST /api/auth/register
     * Body: {"email": "user@example.com", "password": "secret123"}
     *
     * Returns: 201 Created with user ID
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // Parse request
        $data = json_decode($request->getContent(), true);

        // Validate input format
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Missing required fields: email, password',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Dispatch Command
            $command = new RegisterUserCommand(
                email: $data['email'],
                plainPassword: $data['password'],
            );

            $envelope = $this->commandBus->dispatch($command);
            $userId = $envelope->last(HandledStamp::class)?->getResult();

            return new JsonResponse([
                'success' => true,
                'message' => 'User registered successfully',
                'userId' => $userId->value(),
            ], Response::HTTP_CREATED);

        } catch (EmailAlreadyExistsException $e) {
            return new JsonResponse([
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);

        } catch (\InvalidArgumentException $e) {
            // Value Object validation error (invalid email format, etc.)
            return new JsonResponse([
                'error' => 'Invalid input',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Throwable $e) {
            $this->logger->error('Registration failed', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'Registration failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Login user and get JWT token
     *
     * POST /api/auth/login
     * Body: {"email": "user@example.com", "password": "secret123"}
     *
     * Returns: 200 OK with JWT token
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // Parse request
        $data = json_decode($request->getContent(), true);

        // Validate input format
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Missing required fields: email, password',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Dispatch Command
            $command = new LoginCommand(
                email: $data['email'],
                plainPassword: $data['password'],
            );

            $envelope = $this->commandBus->dispatch($command);
            $tokenDTO = $envelope->last(HandledStamp::class)?->getResult();

            return new JsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'data' => $tokenDTO->toArray(),
            ], Response::HTTP_OK);

        } catch (InvalidCredentialsException $e) {
            return new JsonResponse([
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);

        } catch (\InvalidArgumentException $e) {
            // Value Object validation error
            return new JsonResponse([
                'error' => 'Invalid input',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Throwable $e) {
            $this->logger->error('Login failed', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => 'Login failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get current authenticated user
     *
     * GET /api/auth/me
     * Headers: Authorization: Bearer <token>
     *
     * Returns: 200 OK with user data
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        // Get current user from Symfony Security
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Dispatch Query
            $query = new GetCurrentUserQuery(
                userId: $user->getUserIdentifier()
            );

            $envelope = $this->queryBus->dispatch($query);
            $userDTO = $envelope->last(HandledStamp::class)?->getResult();

            if (!$userDTO) {
                return new JsonResponse([
                    'error' => 'User not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $userDTO->toArray(),
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get current user', [
                'userId' => $user->getUserIdentifier(),
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
