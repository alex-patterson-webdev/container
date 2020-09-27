<?php

declare(strict_types=1);

namespace ArpTest\Container\Provider;

use Arp\Container\Provider\ConfigServiceProvider;
use Arp\Container\Provider\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Arp\Container\Provider\ConfigServiceProvider
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\Container\Provider
 */
final class ConfigServiceProviderTest extends TestCase
{
    /**
     * Assert that class implements ServiceProviderInterface
     */
    public function testImplementsServiceProviderInterface(): void
    {
        $serviceProvider = new ConfigServiceProvider([]);

        $this->assertInstanceOf(ServiceProviderInterface::class, $serviceProvider);
    }
}
