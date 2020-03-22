<?php

declare(strict_types=1);

namespace Arp\ContainerArray;

use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\NotFoundException;
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
     * An array of services that have been added or created
     *
     * @var mixed[]
     */
    private $services = [];

    /**
     * An array of service names to factory classes
     *
     * @var callable[]
     */
    private $factories = [];

    /**
     * @param string $name Identifier of the entry to look for
     *
     * @return mixed
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->services)) {
            return $this->services[$name];
        }

        if (! array_key_exists($name, $this->factories)) {
            throw new NotFoundException(sprintf('Service \'%s\' could not be found', $name));
        }

        $factory = $this->factories[$name];

        if (is_string($factory)) {
            $factory = new $factory();
        }

        if (! is_callable($factory)) {
            throw new ContainerException(sprintf('The factory for service \'%s\' is not callable', $name));
        }

        try {
            $this->services[$name] = $factory($this);
        } catch (\Throwable $e) {
            throw new ContainerException(
                sprintf('An error occurred while creating service \'%s\' : %s', $name, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }

        return $this->services[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name): bool
    {
        return array_key_exists($name, $this->services) || array_key_exists($name, $this->factories);
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
     * Set a callable factory for service with $name.
     *
     * @param string   $name
     * @param callable $service
     *
     * @return $this
     */
    public function setFactory($name, callable $service): self
    {
        $this->factories[$name] = $service;

        return $this;
    }

    /**
     * Set a factory class name
     *
     * @param string $name
     * @param string $factoryClass
     *
     * @return $this
     */
    public function setFactoryClass(string $name, string $factoryClass): self
    {
        $this->factories[$name] = $factoryClass;

        return $this;
    }

    /**
     * Create a new instance of the requested service
     *
     * @param string $name    The name of the service to create
     * @param array  $options The optional creation options
     *
     * @return mixed
     *
     * @throws ContainerException If the creation of the requested service fails
     * @throws NotFoundException If the service cannot by found
     */
    public function create(string $name, array $options = [])
    {
        $factory = null;

        if (array_key_exists($name, $this->factories)) {
            $factory = $this->factories[$name];

            if (is_string($factory)) {
                $factory = new $factory();
            }
        }

        if (null === $factory || ! is_callable($factory)) {
            throw new NotFoundException(
                sprintf(
                    'Unable to find an invokable factory for service \'%s\'',
                    $name
                )
            );
        }

        return $factory($this, $name, $options);
    }
}
