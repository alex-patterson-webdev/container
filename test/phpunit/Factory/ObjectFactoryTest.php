<?php

declare(strict_types=1);

namespace ArpTest\Container\Factory;

use Arp\Container\Factory\ObjectFactory;
use Arp\Container\Factory\ServiceFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Arp\Container\Factory\ObjectFactory
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\Container\Factory
 */
final class ObjectFactoryTest extends TestCase
{
    /**
     * Assert that the object factory implements ServiceFactoryInterface
     */
    public function testImplementsServiceFactoryInterface(): void
    {
        $factory = new ObjectFactory();

        $this->assertInstanceOf(ServiceFactoryInterface::class, $factory);
    }
}
