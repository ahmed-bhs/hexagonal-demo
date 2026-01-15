<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\Jwt;

use App\Security\Authentication\Domain\Port\TokenGeneratorInterface;
use App\Security\User\Domain\Model\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Firebase JWT Token Generator (Adapter)
 *
 * Implements TokenGeneratorInterface using Firebase JWT library.
 * Can be swapped with other JWT libraries (Lexik, etc.)
 */
final readonly class FirebaseJwtTokenGenerator implements TokenGeneratorInterface
{
    public function __construct(
        private string $secret,
        private string $issuer,
        private int $ttl = 3600, // 1 hour
    ) {}

    public function generateToken(User $user): string
    {
        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify(sprintf('+%d seconds', $this->ttl));

        $payload = [
            'iss' => $this->issuer,           // Issuer
            'iat' => $now->getTimestamp(),    // Issued at
            'exp' => $expiresAt->getTimestamp(), // Expiration
            'sub' => $user->id()->value(),    // Subject (user ID)
            'email' => $user->email()->value(),
            'roles' => $user->roles(),
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function parseToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            return [
                'userId' => $decoded->sub,
                'email' => $decoded->email,
                'roles' => $decoded->roles,
            ];
        } catch (\Exception $e) {
            // Token invalid or expired
            return null;
        }
    }
}
