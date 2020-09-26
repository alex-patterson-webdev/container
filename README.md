[![Build Status](https://travis-ci.com/alex-patterson-webdev/container-array.svg?branch=master)](https://travis-ci.com/alex-patterson-webdev/container)
[![codecov](https://codecov.io/gh/alex-patterson-webdev/container-array/branch/master/graph/badge.svg)](https://codecov.io/gh/alex-patterson-webdev/container)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alex-patterson-webdev/container-array/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alex-patterson-webdev/container/?branch=master)

# Arp\Container

## About

A PSR-11 compatible Dependency Injection Container which used array configuration for service registration
and factories for inversion of control
 
## Installation

Installation via [Composer](https://getcomposer.org).

    require alex-patterson-webdev/container ^1
 
## Usage

To begin using the container, we simply need to create an instance of it

    use Arp\Container\Container;
    
    $container = new Container();

The `Arp\Container\Container` implements the `Psr\ContainerInterface` and therefore can be used to check and fetch services by name.

    use Arp\Container\Container;
    
    if (true === $container->has('ServiceName')) {
        $service = $container->get('ServiceName');
    }
    
## Registering Services

In order to be able to fetch services from the container, we must first register them. All services require a unique name to be provided when registering.
This name is the value we use after to fetch the service via `$container->get()`. There are a number of different ways we can register a service with the container,
the method you choose will depend on how you wish the service to be created by the container or if the service when created has other dependencies to resolve.

### Objects and Values

The simplest use case is when you need to add an object or value to the container. As these values not require instantiation or other dependencies the 
container will simply store and return this value unmodified when requested from the container.

    $container = new Container();
    $container->setService('TodaysDate', new \DateTime('today'));
    $todaysDate = $container->get('TodaysDate');
       
### Classes Registration

The container is able to create services from factory classes. If your class has no dependencies, and the name you register matches the fully qualified class name, calls to get
will create the class using the `Arp\Container\Factory\ObjectFactory` automatically, no service registration is required.

        use Arp\Container\Factory\ObjectFactory;
    
        $container = new Container();
        
        // Create an new object instance from a non-registered service name 
        // where the service name matches the FQCN
        $object = $container->get(\stdClass::class);

It is recommended for clarity that you explicitly define the service using  `Arp\Container\Factory\ObjectFactory`.

    use Arp\Container\Factory\ObjectFactory;

    $container = new Container();
    $container->setFactory(\stdClass::class, ObjectFactory::class); 
    
    // @var \stdClass $object
    $object = $container->get(\stdClass::class);

You can also define you own factories for more complex object creation. Factories can be of any php `callable` type and 
allow the service name to differ from the created service.

    use Arp\Container\Factory\ObjectFactory;

    $container = new Container();
    $container->setFactory('FooService', static function() {
        return new \stdClass();
    }); 
    
    // @var \stdClass $object
    $object = $container->get('FooService);

The factory 'callables' are also provided with a number of arguments to allow us to resolve other dependencies inside 
other service factories.

