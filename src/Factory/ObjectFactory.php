<?php

declare(strict_types=1);

namespace Arp\Container\Factory;

use Arp\Container\Factory\Exception\ServiceFactoryException;
use Psr\Container\ContainerInterface;

/**
 * Factory which will create a new instance of a class based on the requested $name
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\Container\Factory
 */
final class ObjectFactory implements ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $name
     * @param array|null         $options
     *
     * @return object
     *
     * @throws ServiceFactoryException If the requested $name of the service is not a valid class name
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null): object
    {
        if (!class_exists($name, true)) {
            throw new ServiceFactoryException(
                sprintf(
                    'Unable to create a new object from requested service \'%s\': '
                    . 'The service  does not resolve to a valid class name',
                    $name
                )
            );
        }

        return (null === $options)
            ? new $name()
            : new $name(...$options);
    }
}
