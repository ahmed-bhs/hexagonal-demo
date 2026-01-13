<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cadeau\Attribution\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Email Value Object - MINIMUM NÉCESSAIRE.
 */
final class EmailTest extends TestCase
{
    #[Test]
    public function it_creates_valid_email(): void
    {
        $email = new Email('john@example.com');
        $this->assertEquals('john@example.com', $email->value);
    }

    #[Test]
    public function it_rejects_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('invalid');
    }
}
