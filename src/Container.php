<?php

declare(strict_types=1);

namespace Arp\Container;

use Arp\Container\Exception\CircularDependencyException;
use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\InvalidArgumentException;
use Arp\Container\Exception\NotFoundException;
use Arp\Container\Factory\ObjectFactory;
use Arp\Container\Factory\ServiceFactoryInterface;
use Arp\Container\Provider\Exception\ServiceProviderException;
use Arp\Container\Provider\ServiceProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\Container
 */
final class Container implements ContainerInterface
{
    /**
     * @var string[]
     */
    private array $aliases = [];

    /**
     * @var mixed[]
     */
    private array $services = [];

    /**
     * @var callable[]
     */
    private array $factories = [];

    /**
     * @var string[]
     */
    private array $factoryClasses = [];

    /**
     * @var array
     */
    private array $requested = [];

    /**
     * @param ServiceProviderInterface|null $serviceProvider
     *
     * @throws ContainerException
     */
    public function __construct(ServiceProviderInterface $serviceProvider = null)
    {
        if (null !== $serviceProvider) {
            $this->configure($serviceProvider);
        }
    }

    /**
     * @param string $name Identifier of the entry to look for
     *
     * @return mixed
     *
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     *
     * @noinspection PhpMissingParamTypeInspection
     */
    public function get($name)
    {
        return $this->doGet($name);
    }

    /**
     * @param string     $name
     * @param array|null $arguments
     *
     * @return mixed
     *
     * @throws ContainerException
     * @throws NotFoundException
     * @throws CircularDependencyException
     */
    private function doGet(string $name, array $arguments = null)
    {
        if (isset($this->aliases[$name])) {
            return $this->doGet($this->aliases[$name]);
        }

        if (isset($this->services[$name])) {
            $service = $this->services[$name];
        } elseif (isset($this->requested[$name])) {
            throw new CircularDependencyException(
                sprintf(
                    'A circular dependency has been detected for service \'%s\'. The dependency graph includes %s',
                    $name,
                    implode(',', array_keys($this->requested))
                )
            );
        } else {
            $factory = $this->resolveFactory($name);
            if (null !== $factory) {
                $this->requested[$name] = true;
                $service = $this->invokeFactory($factory, $name, $arguments);
                $this->set($name, $service);
                unset($this->requested[$name]);
            }
        }

        if (isset($service)) {
            return $service;
        }

        throw new NotFoundException(
            sprintf('Service \'%s\' could not be found registered with the container', $name)
        );
    }

    /**
     * Create a new service with the provided options. Services required via build will always have a new instance
     * of the service returned. Only services registered with factories can be built.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws NotFoundException
     */
    public function build(string $name, array $arguments = [])
    {
        if (isset($this->aliases[$name])) {
            return $this->build($this->aliases[$name]);
        }

        $factory = $this->resolveFactory($name);
        if (null === $factory) {
            throw new NotFoundException(
                sprintf('Unable to build service \'%s\': No valid factory could be found', $name)
            );
        }

        return $this->invokeFactory($factory, $name, $arguments);
    }

    /**
     * @param string $name
     *
     * @return bool
     *
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function has($name)
    {
        return $this->doHas($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function doHas(string $name): bool
    {
        return isset($this->services[$name])
            || isset($this->factories[$name])
            || isset($this->aliases[$name])
            || isset($this->factoryClasses[$name]);
    }

    /**
     * Set a service on the container
     *
     * @param string $name
     * @param mixed  $service
     *
     * @return $this
     */
    public function set(string $name, $service): self
    {
        $this->services[$name] = $service;

        return $this;
    }

    /**
     * Register a factory for the container.
     *
     * @param string          $name    The name of the service to register.
     * @param string|callable $factory The factory callable responsible for creating the service.
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the provided factory is not string or callable
     */
    public function setFactory(string $name, $factory): self
    {
        if (is_string($factory)) {
            return $this->setFactoryClass($name, $factory);
        }

        if (!is_callable($factory)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The \'factory\' argument must be of type \'string\' or \'callable\';'
                    . '\'%s\' provided for service \'%s\'',
                    is_object($factory) ? get_class($factory) : gettype($factory),
                    $name
                )
            );
        }

        $this->factories[$name] = $factory;

        return $this;
    }

    /**
     * Set the class name of a factory that will create service $name.
     *
     * @param string      $name         The name of the service to set the factory for.
     * @param string      $factoryClass The fully qualified class name of the factory.
     * @param string|null $method       The name of the factory method to call.
     *
     * @return $this
     */
    public function setFactoryClass(string $name, string $factoryClass, string $method = null): self
    {
        $this->factoryClasses[$name] = [$factoryClass, $method];

        return $this;
    }

    /**
     * Set an alias for a given service
     *
     * @param string $alias The name of the alias to set
     * @param string $name  The name of the service that
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setAlias(string $alias, string $name): self
    {
        if (!isset($this->services[$name]) && !isset($this->factories[$name]) && !isset($this->factoryClasses[$name])) {
            throw new InvalidArgumentException(
                sprintf('Unable to configure alias \'%s\' for unknown service \'%s\'', $alias, $name)
            );
        }

        if ($alias === $name) {
            throw new InvalidArgumentException(
                sprintf('Unable to configure alias \'%s\' with identical service name \'%s\'', $alias, $name)
            );
        }

        $this->aliases[$alias] = $name;

        return $this;
    }

    /**
     * @param callable   $factory
     * @param string     $name
     * @param array|null $options
     *
     * @return mixed
     *
     * @throws ContainerExceptionInterface
     */
    private function invokeFactory(callable $factory, string $name, array $options = null)
    {
        try {
            return $factory($this, $name, $options);
        } catch (ContainerExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ContainerException(
                sprintf('The service \'%s\' could not be created: %s', $name, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $name
     *
     * @return callable|null
     *
     * @throws ContainerException
     */
    private function resolveFactory(string $name): ?callable
    {
        $factory = null;
        if (isset($this->factories[$name])) {
            $factory = $this->factories[$name];
        } elseif (isset($this->factoryClasses[$name][0])) {
            $factory = $this->resolveFactoryClass(
                $name,
                $this->factoryClasses[$name][0],
                $this->factoryClasses[$name][0] ?? null
            );
        } elseif (class_exists($name, true)) {
            $factory = $this->createObjectFactory();
        }

        if (null !== $factory && !is_callable($factory)) {
            throw new ContainerException(sprintf('Factory registered for service \'%s\', must be callable', $name));
        }

        return $factory;
    }

    /**
     * @return ServiceFactoryInterface
     */
    private function createObjectFactory(): ServiceFactoryInterface
    {
        return new ObjectFactory();
    }

    /**
     * @param string      $name
     * @param string      $factoryClassName
     * @param string|null $methodName
     *
     * @return array
     *
     * @throws ContainerException
     */
    private function resolveFactoryClass(string $name, string $factoryClassName, ?string $methodName): array
    {
        if ($factoryClassName === $name) {
            throw new ContainerException(
                sprintf('A circular configuration dependency was detected for service \'%s\'', $name)
            );
        }

        if (class_exists($factoryClassName, true) && !$this->has($factoryClassName)) {
            $this->setFactory($factoryClassName, $this->createObjectFactory());
        }

        if (!$this->has($factoryClassName)) {
            throw new ContainerException(
                sprintf(
                    'The factory service \'%s\', registered for service \'%s\', is not a valid service or class name',
                    $factoryClassName,
                    $name
                )
            );
        }

        return [$this->get($factoryClassName), $methodName ?? '__invoke'];
    }

    /**
     * @param ServiceProviderInterface $serviceProvider
     *
     * @throws ContainerException
     */
    public function configure(ServiceProviderInterface $serviceProvider): void
    {
        try {
            $serviceProvider->registerServices($this);
        } catch (ServiceProviderException $e) {
            throw new ContainerException(
                sprintf(
                    'Failed to register services using provider \'%s\': %s',
                    get_class($serviceProvider),
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }
    }
}
