<?php

declare(strict_types=1);

namespace Arp\Container;

use Arp\Container\Exception\CircularDependencyException;
use Arp\Container\Exception\ContainerException;
use Arp\Container\Exception\InvalidArgumentException;
use Arp\Container\Exception\NotFoundException;
use Arp\Container\Provider\ServiceProviderInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\Container
 */
interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * Set a service on the container
     *
     * @param string $name
     * @param mixed  $service
     *
     * @return $this
     */
    public function set(string $name, $service): self;

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
    public function setFactory(string $name, $factory): self;

    /**
     * Set the class name of a factory that will create service $name.
     *
     * @param string      $name         The name of the service to set the factory for.
     * @param string      $factoryClass The fully qualified class name of the factory.
     * @param string|null $method       The name of the factory method to call.
     *
     * @return $this
     */
    public function setFactoryClass(string $name, string $factoryClass, string $method = null): self;

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
    public function setAlias(string $alias, string $name): self;

    /**
     * Create a new $name with the provided $arguments. Services will always have a new instance of the service
     * returned. Only services registered with factories can be built.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function build(string $name, array $arguments = []);

    /**
     * Configure the container's service with the provided $serviceProvider
     *
     * @param ServiceProviderInterface $serviceProvider
     *
     * @throws ContainerException
     */
    public function configure(ServiceProviderInterface $serviceProvider): void;
}
