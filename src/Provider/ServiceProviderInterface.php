<?php

declare(strict_types=1);

namespace Arp\Container\Provider;

use Arp\Container\Container;
use Arp\Container\Provider\Exception\ServiceProviderException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\Container\Provider
 */
interface ServiceProviderInterface
{
    /**
     * Register services with the container.
     *
     * @param Container $container
     *
     * @throws ServiceProviderException
     */
    public function registerServices(Container $container): void;
}
