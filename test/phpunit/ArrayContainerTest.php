<?php

declare(strict_types=1);

namespace ArpTest\ContainerArray;

use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\NotFoundException;
use Arp\ContainerArray\ArrayContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\ContainerArray
 */
final class ArrayContainerTest extends TestCase
{
    /**
     * Assert that the ArrayContainer implements ContainerInterface.
     *
     * @covers \Arp\ContainerArray\ArrayContainer
     */
    public function testImplementsContainerInterface(): void
    {
        $container = new ArrayContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    /**
     * Assert that a value can be set and returned from the container.
     *
     * @covers \Arp\ContainerArray\ArrayContainer::set
     * @covers \Arp\ContainerArray\ArrayContainer::get
     */
    public function testSetAndGet(): void
    {
        $container = new ArrayContainer();

        $this->assertSame($container, $container->set('Foo', 'Test'));
        $this->assertSame('Test', $container->get('Foo'));
    }

    /**
     * Assert that has() will return a boolean value for the requested service.
     *
     * @covers \Arp\ContainerArray\ArrayContainer::has
     */
    public function testHas(): void
    {
        $container = new ArrayContainer();

        $this->assertFalse($container->has('Foo'));

        $container->set('Foo', 'FooService');

        $this->assertTrue($container->has('Foo'));
    }

    /**
     * Assert that the container will throw a NotFoundException if the requested service cannot be found.
     *
     * @covers \Arp\ContainerArray\ArrayContainer::get
     */
    public function testGetWillThrowNotFoundExceptionIfRequestedServiceIsNotRegistered(): void
    {
        $container = new ArrayContainer();

        $name = 'FooService';

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(sprintf('Service \'%s\' could not be found', $name));

        $container->get($name);
    }

    /**
     * Assert that a invalid/non-callable factory class will throw a ContainerException.
     *
     * @covers \Arp\ContainerArray\ArrayContainer::get
     * @covers \Arp\ContainerArray\ArrayContainer::setFactoryClass
     */
    public function testGetWillThrowContainerExceptionIfTheRegisteredFactoryIsNotCallable(): void
    {
        $container = new ArrayContainer();

        $name = 'FooService';
        $factory = \stdClass::class;

        $container->setFactoryClass($name, $factory);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(sprintf('The factory for service \'%s\' is not callable', $name));

        $container->get($name);
    }

    /**
     * Assert that the container will throw a ContainerException is the registered factory throws an exception.
     *
     * @covers \Arp\ContainerArray\ArrayContainer::get
     */
    public function testFactoryCreationErrorWillBeCaughtAndRethrownAsContainerException(): void
    {
        $container = new ArrayContainer();

        $name = 'FooService';
        $exceptionMessage = 'This is a test exception message';

        $factory = static function () use ($exceptionMessage) : void {
            throw new \RuntimeException($exceptionMessage);
        };

        $container->setFactory($name, $factory);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(sprintf(
            'An error occurred while creating service \'%s\' : %s',
            $name,
            $exceptionMessage
        ));

        $container->get($name);
    }
}
