<?php

declare(strict_types=1);

namespace ArpTest\ContainerArray;

use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\NotFoundException;
use Arp\ContainerArray\ArrayContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @covers \Arp\ContainerArray\ArrayContainer
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\ContainerArray
 */
final class ArrayContainerTest extends TestCase
{
    /**
     * Assert that the ArrayContainer implements ContainerInterface.
     */
    public function testImplementsContainerInterface(): void
    {
        $container = new ArrayContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    /**
     * Assert that a non string value, $name, will raise a ContainerException when passed to get.
     *
     * @param mixed $name
     *
     * @dataProvider getGetWithNonStringWillThrowInvalidArgumentExceptionData
     *
     * @throws ContainerExceptionInterface
     */
    public function testGetWithNonStringWillThrowInvalidArgumentException($name): void
    {
        $container = new ArrayContainer();

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage(
            sprintf(
                'The \'name\' argument must be of type \'string\'; \'%s\' provided in \'%s\'',
                gettype($name),
                'get'
            )
        );

        $container->get($name);
    }

    /**
     * @return array
     */
    public function getGetWithNonStringWillThrowInvalidArgumentExceptionData(): array
    {
        return [
            [true],
            [123],
            [new \stdClass()]
        ];
    }

    /**
     * Assert that has() will return true for a service that has been set on the container
     *
     * @throws ContainerExceptionInterface
     */
    public function testHasWillAssertBooleanForMatchingService(): void
    {
        $container = new ArrayContainer();

        $name = \stdClass::class;
        $service = new \stdClass();

        $this->assertFalse($container->has($name));

        $container->set($name, $service);

        $this->assertTrue($container->has($name));
    }

    /**
     * Assert that a value can be set and returned from the container.
     */
    public function testAServiceThatIsSetWillBeReturnedByGet(): void
    {
        $container = new ArrayContainer();

        $name = \stdClass::class;
        $service = new \stdClass();

        $container->set($name, $service);

        $this->assertSame($service, $container->get($name));
    }

    /**
     * Assert that the container will throw a NotFoundException if the requested service cannot be found.
     *
     * @throws NotFoundExceptionInterface
     */
    public function testGetWillThrowNotFoundExceptionIfRequestedServiceIsNotRegistered(): void
    {
        $container = new ArrayContainer();

        $name = 'FooService';

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Service \'%s\' could not be found registered with the container', $name)
        );

        $container->get($name);
    }

    /**
     * Assert that a invalid/non-callable factory class will throw a ContainerException.
     */
    public function testGetWillThrowContainerExceptionIfTheRegisteredFactoryIsNotCallable(): void
    {
        $container = new ArrayContainer();

        $name = 'FooService';
        $factory = \stdClass::class;

        $container->setFactoryClass($name, $factory);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to create service \'%s\': The registered factory is not callable', $name)
        );

        $container->get($name);
    }

    /**
     * Assert that the container will throw a ContainerException is the registered factory throws an exception.
     */
    public function testFactoryCreationErrorWillBeCaughtAndRethrownAsContainerException(): void
    {
        $container = new ArrayContainer();

        $name = 'FooService';
        $exceptionMessage = 'This is another test exception message';

        $factory = static function () use ($exceptionMessage): void {
            throw new \RuntimeException($exceptionMessage);
        };

        $container->setFactory($name, $factory);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf('The service \'%s\' could not be created: %s', $name, $exceptionMessage)
        );

        $container->get($name);
    }
}
