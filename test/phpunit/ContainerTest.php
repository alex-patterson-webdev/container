<?php

declare(strict_types=1);

namespace ArpTest\Container;

use Arp\Container\Container;
use Arp\Container\Exception\CircularDependencyException;
use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\InvalidArgumentException;
use Arp\Container\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @covers \Arp\Container\Container
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\ContainerArray
 */
final class ContainerTest extends TestCase
{
    /**
     * Assert that the Container implements ContainerInterface.
     */
    public function testImplementsContainerInterface(): void
    {
        $container = new Container();

        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    /**
     * Assert that has() will return true for a service that has been set on the container
     *
     * @throws ContainerExceptionInterface
     */
    public function testHasWillAssertBooleanTrueForRegisteredService(): void
    {
        $container = new Container();

        $name = \stdClass::class;
        $service = new \stdClass();

        $this->assertFalse($container->has($name));

        $container->set($name, $service);

        $this->assertTrue($container->has($name));
    }

    /**
     * Assert that has() will return FALSE for a service that has NOT been set on the container
     *
     * @throws ContainerExceptionInterface
     */
    public function testHasWillAssertBooleanFalseForNonRegisteredService(): void
    {
        $container = new Container();

        $name = \stdClass::class;

        $this->assertFalse($container->has($name));
    }

    /**
     * Assert that a value can be set and returned from the container.
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetWillReturnAServiceByName(): void
    {
        $container = new Container();

        $name = \stdClass::class;
        $service = new \stdClass();

        $container->set($name, $service);

        $this->assertSame($service, $container->get($name));
    }

    /**
     * Assert that calls to get with a registered service alias will return the named service
     *
     * @throws ContainerExceptionInterface
     */
    public function testGetWillReturnAServiceByAliasName(): void
    {
        $container = new Container();

        $alias = 'foo';
        $name = \stdClass::class;
        $service = new \stdClass();

        $container->set($name, $service);
        $container->setAlias($alias, $name);

        $this->assertSame($service, $container->get($alias));
    }

    /**
     * Assert that the container will throw a NotFoundException if the requested service cannot be found.
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetWillThrowNotFoundExceptionIfRequestedServiceIsNotRegistered(): void
    {
        $container = new Container();

        $name = 'FooService';

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Service \'%s\' could not be found registered with the container', $name)
        );

        $container->get($name);
    }

    /**
     * Assert that a invalid/non-callable factory class will throw a ContainerException.
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetWillThrowContainerExceptionIfTheRegisteredFactoryIsNotCallable(): void
    {
        $container = new Container();

        $name = 'FooService';
        $factoryClassName = \stdClass::class;

        $container->setFactoryClass($name, $factoryClassName);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Factory \'%s\' registered for service \'%s\', must be callable',
                $factoryClassName,
                $name
            )
        );

        $container->get($name);
    }

    /**
     * Assert that circular dependencies between a service name and it's factory are resolved by throwing
     * a ContainerException
     *
     * @throws ContainerExceptionInterface
     */
    public function testCircularConfigurationDependencyWithFactoryClassNameWillThrowContainerException(): void
    {
        $name = CallableMock::class;
        $factoryClassName = CallableMock::class;

        $container = new Container();
        $container->setFactoryClass($name, $factoryClassName);

        $this->expectException(ContainerException::class);
        $this->expectDeprecationMessage(
            sprintf('A circular configuration dependency was detected for service \'%s\'', $name)
        );

        $container->get($name);
    }

    /**
     * Assert that the container will throw a ContainerException is the registered factory throws an exception.
     *
     * @throws ContainerException
     */
    public function testFactoryCreationErrorWillBeCaughtAndRethrownAsContainerException(): void
    {
        $container = new Container();

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

    /**
     * Assert that an unregistered service, which resolves to the name of a valid class, will be created and
     * registered with the container. Additional calls to the container's get() method should also return the same
     * service
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetWillCreateAndReturnUnregisteredServiceIfTheNameResolvesToAValidClassName(): void
    {
        $container = new Container();

        $name = \stdClass::class;
        $this->assertFalse($container->has($name));
        $service = $container->get(\stdClass::class);

        $this->assertInstanceOf($name, $service);
        $this->assertTrue($container->has($name));
        $this->assertSame($service, $container->get($name));
    }

    /**
     * When creating factories with dependencies, ensure we catch any attempts to load services that depend on each
     * other by throwing a ContainerException
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function testGetWillThrowContainerExceptionIfAFactoryDependencyCausesACircularCreationDependency(): void
    {
        $container = new Container();

        $factoryA = static function (ContainerInterface $container) {
            $serviceA = new \stdClass();
            $serviceA->serviceB = $container->get('ServiceB');
            return $serviceA;
        };

        $factoryB = static function (ContainerInterface $container) {
            $serviceB = new \stdClass();
            $serviceB->serviceA = $container->get('ServiceA');
            return $serviceB;
        };

        $container->setFactory('ServiceA', $factoryA);
        $container->setFactory('ServiceB', $factoryB);

        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage(
            sprintf(
                'A circular dependency has been detected for service \'%s\'. The dependency graph includes %s',
                'ServiceA',
                implode(',', ['ServiceA', 'ServiceB'])
            )
        );

        $container->get('ServiceA');
    }
}
