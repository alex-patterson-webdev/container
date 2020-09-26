<?php

declare(strict_types=1);

namespace ArpTest\Container;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\Container
 */
class CallableMock
{
    public function __invoke(): void
    {
    }
}
