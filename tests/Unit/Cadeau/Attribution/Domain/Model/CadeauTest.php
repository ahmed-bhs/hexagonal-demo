<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cadeau\Attribution\Domain\Model;

use App\Cadeau\Attribution\Domain\Model\Cadeau;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Cadeau Entity - MINIMUM NÉCESSAIRE.
 */
final class CadeauTest extends TestCase
{
    #[Test]
    public function it_creates_cadeau(): void
    {
        $cadeau = Cadeau::create('cad-1', 'Vélo', 'Description', 10);

        $this->assertEquals('cad-1', $cadeau->getId());
        $this->assertEquals('Vélo', $cadeau->getNom());
        $this->assertEquals(10, $cadeau->getQuantite());
    }

    #[Test]
    public function it_checks_if_in_stock(): void
    {
        $inStock = Cadeau::create('cad-1', 'Vélo', 'Description', 5);
        $outOfStock = Cadeau::create('cad-2', 'Ballon', 'Description', 0);

        $this->assertTrue($inStock->isEnStock());
        $this->assertFalse($outOfStock->isEnStock());
    }

    #[Test]
    public function it_checks_availability(): void
    {
        $cadeau = Cadeau::create('cad-1', 'Vélo', 'Description', 5);

        $this->assertTrue($cadeau->estDisponible(3));
        $this->assertTrue($cadeau->estDisponible(5));
        $this->assertFalse($cadeau->estDisponible(10));
    }
}
