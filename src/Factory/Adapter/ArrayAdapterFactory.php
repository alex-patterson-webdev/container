<?php

declare(strict_types=1);

namespace Arp\ContainerArray\Factory\Adapter;

use Arp\ContainerArray\Adapter\ArrayAdapter;
use Arp\ContainerArray\ArrayContainer;
use Arp\Factory\Exception\FactoryException;
use Arp\Factory\FactoryInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\ContainerArray\Factory\Adapter
 */
class ArrayAdapterFactory implements FactoryInterface
{
    /**
     * @param array $config The optional factory configuration options.
     *
     * @return ArrayAdapter
     *
     * @throws FactoryException If the service cannot be created.
     */
    public function create(array $config = []): ArrayAdapter
    {
        $arrayContainer = $config['container'] ?? new ArrayContainer();

        if (! $arrayContainer instanceof ArrayContainer) {
            throw new FactoryException(
                sprintf(
                    'The \'container\' argument must be an object of type \'%s\'; \'%s\' provided in \'%s\'',
                    ArrayContainer::class,
                    (is_object($arrayContainer) ? get_class($arrayContainer) : gettype($arrayContainer)),
                    static::class
                )
            );
        }

        return new ArrayAdapter($arrayContainer);
    }
}
