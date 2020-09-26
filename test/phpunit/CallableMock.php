<?php

declare(strict_types=1);

namespace ArpTest\ContainerArray;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\ContainerArray
 */
class CallableMock
{
    public function __invoke(): void
    {
    }
}
