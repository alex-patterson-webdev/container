<?php

declare(strict_types=1);

namespace ArpTest\Container;

use Arp\Container\Container;
use Arp\Container\Exception\CircularDependencyException;
use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\InvalidArgumentException;
use Arp\Container\Exception\NotFoundException;
use Arp\Container\Provider\Exception\ServiceProviderException;
use Arp\Container\Provider\ServiceProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @covers  \Arp\Container\Container
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
     * Assert that our README code example is working
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testTheDateOfTodayExampleInReadMe(): void
    {
        $container = new Container();

        $name = 'TheDateOfToday';
        $service = new \DateTime('today');
        $container->set($name, $service);

        $this->assertSame($service, $container->get($name));
    }

    /**
     * Assert that a ContainerException is thrown when trying to register a service alias for an unregistered service
     *
     * @throws InvalidArgumentException
     */
    public function testSetAliasWillThrowContainerExceptionIfTheServiceNameAliasedHasNotBeenRegistered(): void
    {
        $container = new Container();

        $alias = 'FooService';
        $name = 'TestService';

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to configure alias \'%s\' for unknown service \'%s\'', $alias, $name)
        );

        $container->setAlias($alias, $name);
    }

    /**
     * Assert that a ContainerException is thrown when trying to register a service alias with a service that
     * has an identical name
     *
     * @throws InvalidArgumentException
     */
    public function testSetAliasWillThrowContainerExceptionIfTheServiceNameIsIdenticalToTheAlias(): void
    {
        $container = new Container();

        $alias = 'TestService';
        $name = 'TestService';

        $container->set($name, new \stdClass());

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to configure alias \'%s\' with identical service name \'%s\'', $alias, $name)
        );

        $container->setAlias($alias, $name);
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
        $this->expectExceptionMessage(sprintf('Factory registered for service \'%s\', must be callable', $name));

        $container->get($name);
    }

    /**
     * Assert that we can pass a factory class name string to setFactory() and the service will be registered
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function testStringFactoryPassedToSetFactoryIsRegistered(): void
    {
        $container = new Container();

        $this->assertFalse($container->has('Test'));
        $container->setFactory('Test', 'ThisIsAFactoryString');
        $this->assertTrue($container->has('Test'));
    }

    /**
     * Assert that a InvalidArgumentException is thrown if trying to set a non-string or not callable $factory
     * when calling setFactory().
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function testSetFactoryWithNonStringOrCallableWillThrowInvalidArgumentException(): void
    {
        $container = new Container();

        $name = \stdClass::class;
        $factory = new \stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The \'factory\' argument must be of type \'string\' or \'callable\';'
                . '\'%s\' provided for service \'%s\'',
                is_object($factory) ? get_class($factory) : gettype($factory),
                $name
            )
        );

        $container->setFactory($name, $factory);
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

    /**
     * When calling get() for a service that has an invalid (not callable) factory class name a ContainerException
     * should be thrown
     *
     * @throws ContainerException
     */
    public function testGetWillThrowContainerExceptionForInvalidRegisteredFactoryClassName(): void
    {
        $container = new Container();

        $serviceName = 'FooService';
        $factoryClassName = 'Foo\\Bar\\ClassNameThatDoesNotExist';

        // We should be able to add the invalid class without issues
        $container->setFactoryClass($serviceName, $factoryClassName);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The factory service \'%s\', registered for service \'%s\', is not a valid service or class name',
                $factoryClassName,
                $serviceName
            )
        );

        // It is only when we requested the service via get that the factory creation should fail
        $container->get($serviceName);
    }

    /**
     * Assert that if we try to build a service and we cannot resolve a factory from then a NotFoundException is thrown
     *
     * @throws ContainerException
     */
    public function testBuildWillThrowNotFoundExceptionIfTheFactoryCannotBeResolvedFromName(): void
    {
        $container = new Container();

        $name = 'FooService';

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to build service \'%s\': No valid factory could be found', $name)
        );

        $container->build($name);
    }

    /**
     * Assert that when creating a service via build(), any previously set service matching the provided $name
     * will be ignored and a new instance will be returned. We additional check that the build also will not modify
     * or change the previous service and calls to get() will return the existing value
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function testBuildWillIgnorePreviouslySetServiceWhenCreatingViaFactory(): void
    {
        $container = new Container();

        $serviceName = 'ServiceName';

        // Define our service
        $container->setFactory(
            $serviceName,
            static function () {
                return new \stdClass();
            }
        );

        // Request it by it's service name  so we 'set' the service
        $service = $container->get($serviceName);

        $builtService = $container->build($serviceName);

        $this->assertInstanceOf(\stdClass::class, $service);
        $this->assertInstanceOf(\stdClass::class, $builtService);

        // The services should not be the same object instance
        $this->assertNotSame($service, $builtService);

        // We expect the existing service to not have been modified and additional calls to get
        // resolve to the existing set service (and will not execute the factory)
        $this->assertSame($service, $container->get($serviceName));
    }


    /**
     * Assert that an alias service name will correctly resolve the the correct service when calling build()
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function testBuildWillResolveAliasToServiceName(): void
    {
        $container = new Container();

        $alias = 'FooAliasName';
        $name = 'FooServiceName';

        // Define our service
        $container->setFactory(
            $name,
            static function () {
                return new \stdClass();
            }
        );
        $container->setAlias($alias, $name);

        $this->assertInstanceOf(\stdClass::class, $container->build($alias));
    }

    /**
     * Assert that configuration errors will raise a ContainerException
     *
     * @throws ContainerException
     */
    public function testConfigureWillThrowAContainerExceptionIfTheConfigurationFails(): void
    {
        $container = new Container();

        /** @var ServiceProviderInterface|MockObject $provider */
        $provider = $this->getMockForAbstractClass(ServiceProviderInterface::class);

        $exceptionMessage = 'This is a test exception message';
        $exceptionCode = 12345;
        $exception = new ServiceProviderException($exceptionMessage, $exceptionCode);

        $provider->expects($this->once())
            ->method('registerServices')
            ->with($container)
            ->willThrowException($exception);

        $this->expectException(ContainerException::class);
        $this->expectExceptionCode($exceptionCode);
        $this->expectExceptionMessage(
            sprintf(
                'Failed to register services using provider \'%s\': %s',
                get_class($provider),
                $exceptionMessage
            )
        );

        $container->configure($provider);
    }

    /**
     * Assert that configure() will correctly configure the expected container services
     *
     * @param bool $viaConstructor
     *
     * @dataProvider getConfigureData
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testConfigure(bool $viaConstructor): void
    {
        $fooService = new \stdClass();
        $barServiceFactory = static function () {
            return new \stdClass();
        };

        $serviceProvider = new class($fooService, $barServiceFactory) implements ServiceProviderInterface {
            private \stdClass $fooService;
            private \Closure $barServiceFactory;

            public function __construct($fooService, $barServiceFactory)
            {
                $this->fooService = $fooService;
                $this->barServiceFactory = $barServiceFactory;
            }

            public function registerServices(Container $container): void
            {
                $container->set('FooService', $this->fooService);
                $container->setFactory('BarService', $this->barServiceFactory);
            }
        };

        if ($viaConstructor) {
            $container = new Container($serviceProvider);
        } else {
            $container = new Container();
            $container->configure($serviceProvider);
        }
        $this->assertSame($fooService, $container->get('FooService'));
        $this->assertInstanceOf(\stdClass::class, $container->get('BarService'));
    }

    /**
     * @return array
     */
    public function getConfigureData(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
