<?php

declare(strict_types=1);

namespace Arp\Container\Factory;

use Arp\Container\Factory\Exception\ServiceFactoryException;
use Psr\Container\ContainerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\Container\Factory
 */
interface ServiceFactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $name
     * @param array|null         $options
     *
     * @return mixed
     *
     * @throws ServiceFactoryException
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null);
}
