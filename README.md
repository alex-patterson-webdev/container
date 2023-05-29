[![Build Status](https://travis-ci.com/alex-patterson-webdev/container-array.svg?branch=master)](https://travis-ci.com/alex-patterson-webdev/container)
[![codecov](https://codecov.io/gh/alex-patterson-webdev/container-array/branch/master/graph/badge.svg)](https://codecov.io/gh/alex-patterson-webdev/container)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alex-patterson-webdev/container-array/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alex-patterson-webdev/container/?branch=master)

# Arp\Container

## About

A simple PSR-11 compatible Dependency Injection Container
 
## Installation

Installation via [Composer](https://getcomposer.org).

    require alex-patterson-webdev/container ^0.1
 
## Usage

To begin using the container, we simply need to create an instance of it

    use Arp\Container\Container;
    
    $container = new Container();

The `Arp\Container\Container` implements the `Psr\ContainerInterface` and therefore can be used to check and fetch services by name.

    if (true === $container->has('ServiceName')) {
        $service = $container->get('ServiceName');
    }
    
## Registering services with the container

There are a number of different ways we can register a 'service' with the container, the method you choose will depend on how you wish the 
service to be created.

### Objects and Values

The simplest use case is when you need to `set()` an object or value on the container. These values do not require 
instantiation, the container will simply store and return this value unmodified when requested via `get()`.

    $container = new Container();
    $container->set('TodaysDate', new \DateTime('today'));
    $todaysDate = $container->get('TodaysDate');
       
### Factories

Factories provide us with a location to construct and resolve dependencies using the container. The factory can be any php `callable`
and can be set by calling `$container->setFactory()`.

    $container = new Container();
    $container->setFactory('TodaysDate', static function() {
        return new \DateTime('today');
    });
    
When invoked the factory class will also have the container injected into it as the first argument. We can use the container to
resolve other dependencies.

    $container->setFactory('TodaysDateService', static function(ContainerInterface $container) {
        return new TodayDateService($container->get('TodaysDate');
    });
    
We also have access to the requested service name as the second argument, `$name`. By being aware of the name of the service which
is being created it allows the creation of reusable factories.

    $factory = static function(ContainerInterface $container, string $name) {
       $todaysDate = $container->get('TodaysDate');
       if ('EnglishDateService' === $name) {
            return new EnglishDateService($todaysDate);
       }
       return new FrenchDateService($todaysDate);
    };
   
We can then assign the same factory with different service names.
   
    $container->setFactory('EnglishDateService', $factory);
    $container->setFactory('FrenchDateService', $factory);
    
### Object Factory

In cases where you need have a service without dependencies we can use the `Arp\Container\Factory\ObjectFactory` and the container will create the
 class for us based on the service `$name`. If the service `$name` is not a valid class name an exception is thrown.
 
    use Arp\Container\Factory\ObjectFactory;
    $container = new Container();
    $container->setFactory(\stdClass(), ObjectFactory::class);
    
    // @var \stdClass $object
    $object = $container->get(\stcClass());
    
_The above configuration isn't explicitly required as any service `$name` using a FQCN not registered with the container 
with be automatically registered to use `ObjectFactory`. We recommended that you explicitly 
define the service for clarity_.

## Unit Tests

The project unit tests can be executed using PHPUnit

    php vendor/bin/phpunit
    
    

