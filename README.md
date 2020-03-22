# Arp\ContainerArray

## About

An array based Implementation of the PSR-11 dependency Injection container.

## Installation

Installation via [Composer](https://getcomposer.org).

    require alex-patterson-webdev/container-array ^1
    
## Usage

The `ArrayAdapter` class joins the `Arp\Container\Container` together

    use Arp\ContainerArray\Factory\Adapter\ArrayAdapterFactory;
    use Arp\Container\Container;

    $adapter = (new ArrayAdapterFactory())->create();
    $container = new Container($adapter);
    
    $foo = $container->get('foo');
