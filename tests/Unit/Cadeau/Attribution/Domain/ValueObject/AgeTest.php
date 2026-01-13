<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cadeau\Attribution\Domain\ValueObject;

use App\Cadeau\Attribution\Domain\ValueObject\Age;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Age Value Object - MINIMUM NÉCESSAIRE.
 */
final class AgeTest extends TestCase
{
    #[Test]
    public function it_creates_valid_age(): void
    {
        $age = new Age(25);
        $this->assertEquals(25, $age->value);
    }

    #[Test]
    public function it_rejects_negative_age(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Age(-1);
    }

    #[Test]
    public function it_rejects_age_exceeding_150(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Age(151);
    }

    #[Test]
    public function it_identifies_adult(): void
    {
        $child = new Age(17);
        $adult = new Age(18);

        $this->assertFalse($child->isAdult());
        $this->assertTrue($adult->isAdult());
    }

    #[Test]
    public function it_identifies_senior(): void
    {
        $adult = new Age(64);
        $senior = new Age(65);

        $this->assertFalse($adult->isSenior());
        $this->assertTrue($senior->isSenior());
    }

    #[Test]
    public function it_identifies_child(): void
    {
        $child = new Age(17);
        $adult = new Age(18);

        $this->assertTrue($child->isChild());
        $this->assertFalse($adult->isChild());
    }
}
