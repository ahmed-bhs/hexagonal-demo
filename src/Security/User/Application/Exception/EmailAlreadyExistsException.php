<?php

declare(strict_types=1);

namespace App\Security\User\Application\Exception;

/**
 * Exception: Email Already Exists
 *
 * Thrown when trying to register with existing email.
 */
final class EmailAlreadyExistsException extends \DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(
            sprintf('Email already exists: %s', $email)
        );
    }
}
