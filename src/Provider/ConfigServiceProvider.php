<?php

declare(strict_types=1);

namespace Arp\Container\Provider;

use Arp\Container\ContainerInterface;
use Arp\Container\Exception\ContainerException;
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
     * @param ContainerInterface $container
     *
     * @throws ServiceProviderException
     */
    public function registerServices(ContainerInterface $container): void
    {
        if (isset($this->config[static::SERVICES]) && is_array($this->config[static::SERVICES])) {
            foreach ($this->config[static::SERVICES] as $name => $service) {
                $container->set($name, $service);
            }
        }

        if (isset($this->config[static::FACTORIES]) && is_array($this->config[static::FACTORIES])) {
            $this->registerFactories($container, $this->config[static::FACTORIES]);
        }

        if (isset($this->config[static::ALIASES]) && is_array($this->config[static::ALIASES])) {
            $this->registerAliases($container, $this->config[static::ALIASES]);
        }
    }

    /**
     * @param ContainerInterface $container
     * @param array              $factories
     *
     * @throws ServiceProviderException
     */
    private function registerFactories(ContainerInterface $container, array $factories): void
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
     * Register a factory that was provided as a configuration array. Using the array format of [$factory, $methodName]
     *
     * @param ContainerInterface $container
     * @param string             $serviceName
     * @param array              $factoryConfig
     *
     * @throws ServiceProviderException
     */
    private function registerArrayFactory(
        ContainerInterface $container,
        string $serviceName,
        array $factoryConfig
    ): void {
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
     * @param ContainerInterface $container
     * @param string             $serviceName
     * @param object|callable    $factory
     * @param string|null        $methodName
     *
     * @throws ServiceProviderException
     */
    private function registerFactory(
        ContainerInterface $container,
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

        try {
            $container->setFactory($serviceName, $factory);
        } catch (ContainerException $e) {
            throw new ServiceProviderException(
                sprintf('Failed to set factory for service \'%s\': %s', $serviceName, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param ContainerInterface $container
     * @param array              $aliases
     *
     * @throws ServiceProviderException
     */
    private function registerAliases(ContainerInterface $container, array $aliases): void
    {
        foreach ($aliases as $aliasName => $serviceName) {
            try {
                $container->setAlias($aliasName, $serviceName);
            } catch (ContainerException $e) {
                throw new ServiceProviderException(
                    sprintf(
                        'Failed to register alias \'%s\' for service \'%s\': %s',
                        $aliasName,
                        $serviceName,
                        $e->getMessage()
                    ),
                    $e->getCode(),
                    $e
                );
            }
        }
    }
}
