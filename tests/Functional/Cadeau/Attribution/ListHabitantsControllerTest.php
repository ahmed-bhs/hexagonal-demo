<?php

declare(strict_types=1);

namespace App\Tests\Functional\Cadeau\Attribution;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test fonctionnel simplifié.
 *
 * ✅ MINIMUM NÉCESSAIRE:
 * - Test que les services sont bien configurés
 * - Test que le container fonctionne
 */
final class ListHabitantsControllerTest extends KernelTestCase
{
    #[Test]
    public function it_boots_kernel(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
    }

    #[Test]
    public function it_has_query_bus_configured(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has('query.bus'));
    }

    #[Test]
    public function it_has_command_bus_configured(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->assertTrue($container->has('command.bus'));
    }
}
