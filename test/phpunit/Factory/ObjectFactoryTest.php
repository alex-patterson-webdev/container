<?php

declare(strict_types=1);

namespace ArpTest\Container\Factory;

use Arp\Container\ContainerInterface;
use Arp\Container\Factory\Exception\ServiceFactoryException;
use Arp\Container\Factory\ObjectFactory;
use Arp\Container\Factory\ServiceFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
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

    /**
     * Assert that if the requested $name of the service does not map to a valid class name, a ServiceFactoryException
     * is thrown from __invoke()
     *
     * @throws ServiceFactoryException
     */
    public function testNameIsInvalidClassNameWillThrowServiceFactoryException(): void
    {
        $factory = new ObjectFactory();

        $name = 'Foo'; // non-existing class name

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->expectException(ServiceFactoryException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to create a new object from requested service \'%s\': '
                . 'The service name does not resolve to a valid class name',
                $name
            )
        );

        $factory($container, $name);
    }

    /**
     * Assert that a new object will be created from the provided service name
     *
     * @dataProvider getInvokeWillCreateObjectFromServiceNameIfValidClassNameData
     *
     * @param string $className
     *
     * @throws ServiceFactoryException
     */
    public function testInvokeWillCreateObjectFromServiceNameIfValidClassName(string $className): void
    {
        $factory = new ObjectFactory();

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);

        $this->assertInstanceOf($className, $factory($container, $className));
    }

    /**
     * @return array
     */
    public function getInvokeWillCreateObjectFromServiceNameIfValidClassNameData(): array
    {
        return [
            [
                \stdClass::class,
            ],
            [
                \DateTime::class,
            ]
        ];
    }
}
