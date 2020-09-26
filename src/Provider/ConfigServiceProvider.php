<?php

declare(strict_types=1);

namespace Arp\Container\Provider;

use Arp\Container\Container;
use Arp\Container\Provider\Exception\ServiceProviderException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\Container\Provider
 */
final class ConfigServiceProvider implements ServiceProviderInterface
{
    public const ALIASES = 'aliases';
    public const FACTORIES = 'factories';
    public const SERVICES = 'services';

    /**
     * @var array
     */
    private array $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param Container $container
     *
     * @throws ServiceProviderException
     */
    public function registerServices(Container $container): void
    {
        try {
            if (isset($this->config[static::SERVICES]) && is_array($this->config[static::SERVICES])) {
                foreach ($this->config[static::SERVICES] as $name => $service) {
                    $container->set($name, $service);
                }
            }

            if (isset($this->config[static::FACTORIES]) && is_array($this->config[static::FACTORIES])) {
                $this->registerFactories($container, $this->config[static::FACTORIES]);
            }

            if (isset($this->config[static::ALIASES]) && is_array($this->config[static::ALIASES])) {
                foreach ($this->config[static::ALIASES] as $name => $alias) {
                    $container->setAlias($alias, $name);
                }
            }
        } catch (\Throwable $e) {
            throw new ServiceProviderException(
                sprintf('Failed to register services with the container: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param Container $container
     * @param array     $factories
     *
     * @throws ServiceProviderException
     */
    private function registerFactories(Container $container, array $factories): void
    {
        foreach ($factories as $name => $factory) {
            if (is_array($factory)) {
                $this->registerArrayFactory($container, $name, $factory);
                continue;
            }

            if (is_string($factory)) {
                $container->setFactoryClass($name, $factory);
                continue;
            }

            $this->registerFactory($container, $name, $factory);
        }
    }

    /**
     * Register a factory that was provided as a configuration array.
     *
     * Using the array format of [$factory, $methodName]
     *
     * $factory can be callable|object|string
     *
     * @param Container $container
     * @param string    $serviceName
     * @param array     $factoryConfig
     *
     * @throws ServiceProviderException
     */
    private function registerArrayFactory(Container $container, string $serviceName, array $factoryConfig): void
    {
        $factory = $factoryConfig[0] ?? null;
        if (null !== $factory) {
            $methodName = $factoryConfig[1] ?? null;

            if (is_string($factory)) {
                $container->setFactoryClass($serviceName, $factory, $methodName);
                return;
            }

            if (is_object($factory) || is_callable($factory)) {
                $this->registerFactory($container, $serviceName, $factory, $methodName);
                return;
            }
        }

        throw new ServiceProviderException(
            sprintf('Failed to register service \'%s\': The provided array configuration is invalid', $serviceName)
        );
    }

    /**
     * @param Container       $container
     * @param string          $serviceName
     * @param object|callable $factory
     * @param string|null     $methodName
     *
     * @throws ServiceProviderException
     */
    private function registerFactory(
        Container $container,
        string $serviceName,
        $factory,
        string $methodName = null
    ): void {
        $methodName = $methodName ?? '__invoke';

        if (!is_callable($factory) && !$factory instanceof \Closure) {
            $factory = [$factory, $methodName];
        }

        if (!is_callable($factory)) {
            throw new ServiceProviderException(
                sprintf('Failed to register service \'%s\': The factory provided is not callable', $serviceName),
            );
        }

        $container->setFactory($serviceName, $factory);
    }
}
