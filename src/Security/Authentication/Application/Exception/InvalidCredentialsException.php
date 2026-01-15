<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Exception;

/**
 * Exception: Invalid Credentials
 *
 * Thrown when login fails (email or password incorrect).
 * Intentionally vague for security.
 */
final class InvalidCredentialsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials');
    }
}
