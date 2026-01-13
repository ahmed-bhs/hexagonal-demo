<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cadeau\Demande\Domain\Model;

use App\Cadeau\Demande\Domain\Model\DemandeCadeau;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DemandeCadeau Entity - MINIMUM NÉCESSAIRE.
 */
final class DemandeCadeauTest extends TestCase
{
    #[Test]
    public function it_creates_demande_in_pending_state(): void
    {
        $demande = DemandeCadeau::create(
            'dem-1',
            'John',
            'john@example.com',
            '0612345678',
            'Vélo',
            'Motivation'
        );

        $this->assertTrue($demande->estEnAttente());
    }

    #[Test]
    public function it_approves_demande(): void
    {
        $demande = DemandeCadeau::create(
            'dem-1',
            'John',
            'john@example.com',
            '0612345678',
            'Vélo',
            'Motivation'
        );

        $demande->approuver();

        $this->assertFalse($demande->estEnAttente());
    }

    #[Test]
    public function it_rejects_demande(): void
    {
        $demande = DemandeCadeau::create(
            'dem-1',
            'John',
            'john@example.com',
            '0612345678',
            'Vélo',
            'Motivation'
        );

        $demande->rejeter();

        $this->assertFalse($demande->estEnAttente());
    }

    #[Test]
    public function it_rejects_double_approval(): void
    {
        $demande = DemandeCadeau::create(
            'dem-1',
            'John',
            'john@example.com',
            '0612345678',
            'Vélo',
            'Motivation'
        );

        $demande->approuver();

        $this->expectException(\DomainException::class);
        $demande->approuver();
    }
}
