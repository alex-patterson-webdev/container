<?php

declare(strict_types=1);

namespace Arp\ContainerArray;

use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\NotFoundException;
use Arp\ContainerArray\Factory\ObjectFactory;
use Arp\ContainerArray\Factory\ServiceFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\Container
 */
final class ArrayContainer implements ContainerInterface
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
     * @param array $config
     *
     * @throws ContainerException
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * @param string $name Identifier of the entry to look for
     *
     * @return mixed
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     */
    private function doGet(string $name, array $arguments = null)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        if (isset($this->aliases[$name])) {
            return $this->get($this->aliases[$name]);
        }

        $factory = $this->resolveFactory($name);
        if (null !== $factory) {
            $service = $this->invokeFactory($factory, $name, $arguments);
            $this->set($name, $service);
            return $service;
        }

        throw new NotFoundException(
            sprintf('Service \'%s\' could not be found registered with the container', $name)
        );
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
     * Register a callable factory for the container.
     *
     * @param string   $name    The name of the service to register.
     * @param callable $factory The factory callable responsible for creating the service.
     *
     * @return $this
     */
    public function setFactory(string $name, callable $factory): self
    {
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
     * @throws ContainerException
     */
    public function setAlias(string $alias, string $name): self
    {
        if (!isset($this->services[$name])) {
            throw new ContainerException(
                sprintf('Unable to configure alias \'%s\' for unknown service \'%s\'', $alias, $name)
            );
        }

        if ($alias === $name) {
            throw new ContainerException(
                sprintf('Unable to configure alias \'%s\' with identical service name \'%s\'', $alias, $name)
            );
        }

        $this->aliases[$alias] = $name;

        return $this;
    }

    /**
     * Create a new service with the provided options
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws ContainerException
     */
    public function build(string $name, array $arguments = [])
    {
        if (isset($this->aliases[$name])) {
            return $this->build($this->aliases[$name]);
        }

        $factory = $this->resolveFactory($name);

        if (null === $factory) {
            throw new ContainerException(
                sprintf('Unable to build service \'%s\': No valid factory could be found', $name)
            );
        }

        return $this->invokeFactory($factory, $name, $arguments);
    }

    /**
     * @param array $config
     *
     * @return $this
     *
     * @throws ContainerException
     */
    public function configure(array $config): self
    {
        if (isset($config['services']) && is_array($config['services'])) {
            foreach ($config['services'] as $name => $service) {
                $this->set($name, $service);
            }
        }

        if (isset($config['factories']) && is_array($config['factories'])) {
            foreach ($config['factories'] as $name => $factory) {
                $this->setFactory($name, $factory);
            }
        }

        if (isset($config['factories_classes']) && is_array($config['factories_classes'])) {
            foreach ($config['factories_classes'] as $name => $factoryClassName) {
                $this->setFactoryClass($name, $factoryClassName);
            }
        }

        if (isset($config['aliases']) && is_array($config['aliases'])) {
            foreach ($config['aliases'] as $name => $alias) {
                $this->setAlias($alias, $name);
            }
        }

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
            $factory = $this->resolveFactoryClass($name);
        } elseif (class_exists($name, true)) {
            $factory = $this->createObjectFactory();
        }

        if (null !== $factory && !is_callable($factory)) {
            throw new ContainerException(
                sprintf('Unable to create service \'%s\': The registered factory is not callable', $name)
            );
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
     * @param string $name
     *
     * @return array
     *
     * @throws ContainerException
     */
    private function resolveFactoryClass(string $name): array
    {
        $factoryClassName = $this->factoryClasses[$name][0] ?? null;

        if (null === $factoryClassName) {
            throw new ContainerException(
                sprintf(
                    'Unable create service \'%s\': The factory class \'%s\' cannot be found',
                    $name,
                    $factoryClassName ?? ''
                )
            );
        }

        if (class_exists($factoryClassName, true) && !$this->has($factoryClassName)) {
            $this->setFactory($factoryClassName, $this->createObjectFactory());
        }

        if ($factoryClassName === $name) {
            throw new ContainerException(
                sprintf(
                    'A circular dependency was detected for service \'%s\' and the registered factory \'%s\'',
                    $name,
                    $factoryClassName
                )
            );
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

        $factory = $this->get($factoryClassName);
        if (!is_callable($factory)) {
            throw new ContainerException(
                sprintf(
                    'Factory \'%s\' registered for service \'%s\', must be callable',
                    $factoryClassName,
                    $name
                )
            );
        }

        return [$factory, $this->factoryClasses[$name][1] ?? '__invoke'];
    }
}
