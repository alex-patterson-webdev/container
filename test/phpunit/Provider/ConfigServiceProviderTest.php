<?php

declare(strict_types=1);

namespace ArpTest\Container\Provider;

use Arp\Container\ContainerInterface;
use Arp\Container\Exception\ContainerException;
use Arp\Container\Provider\ConfigServiceProvider;
use Arp\Container\Provider\Exception\ServiceProviderException;
use Arp\Container\Provider\ServiceProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers  \Arp\Container\Provider\ConfigServiceProvider
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\Container\Provider
 */
final class ConfigServiceProviderTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->container = $this->getMockForAbstractClass(ContainerInterface::class);
    }

    /**
     * Assert that class implements ServiceProviderInterface
     */
    public function testImplementsServiceProviderInterface(): void
    {
        $serviceProvider = new ConfigServiceProvider([]);

        $this->assertInstanceOf(ServiceProviderInterface::class, $serviceProvider);
    }

    /**
     * @throws ServiceProviderException
     */
    public function testRegisterServicesWillThrowServiceProviderExceptionIfAFactoryCannotBeSet(): void
    {
        $serviceName = 'Foo';
        $serviceFactory = static function (): \stdClass {
            return new \stdClass();
        };

        $config = [
            'factories' => [
                $serviceName => $serviceFactory,
            ],
        ];

        $exceptionMessage = 'This is a test exception message';
        $exceptionCode = 3456;
        $exception = new ContainerException($exceptionMessage, $exceptionCode);

        $this->container->expects($this->once())
            ->method('setFactory')
            ->with($serviceName, $serviceFactory)
            ->willThrowException($exception);

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionCode($exceptionCode);
        $this->expectExceptionMessage(
            sprintf('Failed to set factory for service \'%s\': %s', $serviceName, $exceptionMessage)
        );

        (new ConfigServiceProvider($config))->registerServices($this->container);
    }

    /**
     * @throws ServiceProviderException
     */
    public function testRegisterServicesWillThrowServiceProviderExceptionIfTheServiceAliasCannotBeSet(): void
    {
        $service = new \stdClass();
        $serviceName = 'FooService';
        $aliasName = 'FooAlias';

        $config = [
            'services' => [
                $serviceName => $service,
            ],
            'aliases'  => [
                $aliasName => $serviceName,
            ],
        ];

        $exceptionMessage = 'Test exception message';
        $exceptionCode = 12345;
        $exception = new ContainerException($exceptionMessage, $exceptionCode);

        $this->container->expects($this->once())
            ->method('set')
            ->with($serviceName, $service);

        $this->container->expects($this->once())
            ->method('setAlias')
            ->with($aliasName, $serviceName)
            ->willThrowException($exception);

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionCode($exceptionCode);
        $this->expectExceptionMessage(
            sprintf(
                'Failed to register alias \'%s\' for service \'%s\': %s',
                $aliasName,
                $serviceName,
                $exceptionMessage
            )
        );

        (new ConfigServiceProvider($config))->registerServices($this->container);
    }

    /**
     * @throws ServiceProviderException
     */
    public function testRegisterServicesWillThrowServiceProviderExceptionIfTheArrayServiceIsInvalid(): void
    {
        $serviceName = 'FooService';

        $config = [
            'factories' => [
                $serviceName => [],
            ],
        ];

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionMessage(
            sprintf('Failed to register service \'%s\': The provided array configuration is invalid', $serviceName)
        );

        (new ConfigServiceProvider($config))->registerServices($this->container);
    }

    /**
     * Assert that register services will correctly register the provided services and factories defined in $config.
     *
     * @param array $config The services that should be set
     *
     * @dataProvider getRegisterServicesWithFactoriesAndServicesData
     *
     * @throws ServiceProviderException
     */
    public function testRegisterServicesWithFactoriesAndServices(array $config): void
    {
        $serviceProvider = new ConfigServiceProvider($config);

        $factories = $config[ConfigServiceProvider::FACTORIES] ?? [];
        $services = $config[ConfigServiceProvider::SERVICES] ?? [];

        $setFactoryArgs = $setServiceArgs = $setFactoryClassArgs = [];

        foreach ($factories as $name => $factory) {
            $methodName = null;

            if (is_array($factory)) {
                $methodName = $factory[1] ?? null;
                $factory = $factory[0] ?? null;

                if (is_string($factory)) {
                    $setFactoryClassArgs[] = [$name, $factory, $methodName];
                    continue;
                }
                if (!is_callable($factory) && !$factory instanceof \Closure) {
                    $factory = [$factory, $methodName];
                }
            }

            if (is_string($factory)) {
                $setFactoryClassArgs[] = [$name, $factory, $methodName];
                continue;
            }
            $setFactoryArgs[] = [$name, $factory];
        }

        foreach ($services as $name => $service) {
            $setServiceArgs[] = [$name, $service];
        }

        $this->container->expects($this->exactly(count($setFactoryClassArgs)))
            ->method('setFactoryClass')
            ->withConsecutive(...$setFactoryClassArgs);

        $this->container->expects($this->exactly(count($setFactoryArgs)))
            ->method('setFactory')
            ->withConsecutive(...$setFactoryArgs);

        $this->container->expects($this->exactly(count($setServiceArgs)))
            ->method('set')
            ->withConsecutive(...$setServiceArgs);

        $serviceProvider->registerServices($this->container);
    }

    /**
     * @return array
     */
    public function getRegisterServicesWithFactoriesAndServicesData(): array
    {
        return [
            [
                [], // empty config test
            ],

            [
                [
                    ConfigServiceProvider::FACTORIES => [
                        'FooService' => static function () {
                            return 'Hi';
                        },
                    ],
                ],
            ],

            [
                [
                    'services' => [
                        'FooService' => new \stdClass(),
                        'BarService' => new \stdClass(),
                        'Baz'        => 123,
                    ],
                ],
            ],

            // Array based registration for non callable factory object with custom method name 'create'
            [
                [
                    'factories' => [
                        'FooService' => [
                            new class {
                                public function create(): \stdClass
                                {
                                    return new \stdClass();
                                }
                            },
                            'create',
                        ],
                    ],
                ],
            ],

            // String and array based registration
            [
                [
                    'factories' => [
                        'FooService' => 'StringFactoryName',
                        'BazService' => ['BazServiceFactory'],
                        'BarService' => ['StringBarServiceFactory', 'methodNameThatWillBeCalled'],
                        'ZapService' => ['ZapServiceFactory', '__invoke'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Assert that a ServiceProviderException is thrown when the provider is unable to register a factory class
     * as the provided values are not callable
     *
     * @throws ServiceProviderException
     */
    public function testArrayFactoryWithNonCallableMethodWillThrowServiceProviderException(): void
    {
        $serviceName = 'FooService';
        $config = [
            'factories' => [
                $serviceName => [new \stdClass(), 'create'], // not-callable
            ],
        ];

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionMessage(
            sprintf('Failed to register service \'%s\': The factory provided is not callable', $serviceName),
        );

        (new ConfigServiceProvider($config))->registerServices($this->container);
    }

    /**
     * Assert that the ServiceProvider supports string factory registration
     *
     * @throws ServiceProviderException
     */
    public function testRegistrationOfStringFactories(): void
    {
        $serviceName = 'Test123';
        $factoryName = \stdClass::class;
        $config = [
            'factories' => [
                $serviceName => $factoryName,
            ],
        ];

        $this->container->expects($this->once())
            ->method('setFactoryClass')
            ->with($serviceName, $factoryName, null);

        // We provide an adapter mock that is doe NOT implement FactoryClassAwareInterface
        (new ConfigServiceProvider($config))->registerServices($this->container);
    }
}
