<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\Security;

use App\Security\Authentication\Domain\Port\TokenGeneratorInterface;
use App\Security\User\Domain\Port\UserRepositoryInterface;
use App\Security\User\Domain\ValueObject\UserId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * JWT Authenticator
 *
 * Symfony Security authenticator that validates JWT tokens.
 * This is the infrastructure adapter that integrates with Symfony Security.
 */
final class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly TokenGeneratorInterface $tokenGenerator,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Check if this authenticator should be used for this request
     */
    public function supports(Request $request): ?bool
    {
        // Apply to all requests that have Authorization header
        return $request->headers->has('Authorization');
    }

    /**
     * Extract and validate JWT token
     */
    public function authenticate(Request $request): Passport
    {
        // Extract token from Authorization header
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('No Bearer token provided');
        }

        $token = substr($authHeader, 7); // Remove "Bearer "

        // Parse token
        $payload = $this->tokenGenerator->parseToken($token);
        if (!$payload) {
            throw new AuthenticationException('Invalid or expired token');
        }

        // Create passport with user badge
        return new SelfValidatingPassport(
            new UserBadge(
                $payload['userId'],
                function (string $userId) {
                    // Load user from repository
                    $user = $this->userRepository->findById(new UserId($userId));
                    if (!$user) {
                        throw new AuthenticationException('User not found');
                    }

                    // Return Symfony UserInterface adapter
                    return new SymfonyUserAdapter($user);
                }
            )
        );
    }

    /**
     * Called when authentication executed and was successful
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // On success, let the request continue
        return null;
    }

    /**
     * Called when authentication failed
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Authentication failed',
            'message' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
