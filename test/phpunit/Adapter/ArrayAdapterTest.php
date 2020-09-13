<?php

declare(strict_types=1);

namespace ArpTest\ContainerArray\Adapter;

use Arp\Container\Adapter\ContainerAdapterInterface;
use Arp\ContainerArray\Adapter\ArrayAdapter;
use Arp\ContainerArray\ArrayContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\ContainerArray\Adapter
 */
final class ArrayAdapterTest extends TestCase
{
    /**
     * @var ArrayContainer|MockObject
     */
    private $container;

    /**
     * Prepare the test case dependency.
     */
    public function setUp(): void
    {
        $this->container = $this->createMock(ArrayContainer::class);
    }

    /**
     * Assert that the adapter is and instance of ContainerAdapterInterface.
     *
     * @covers \Arp\ContainerArray\Adapter\ArrayAdapter
     */
    public function testImplementsContainerAdapterInterface(): void
    {
        $adapter = new ArrayAdapter($this->container);

        $this->assertInstanceOf(ContainerAdapterInterface::class, $adapter);
    }
}
