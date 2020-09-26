[![Build Status](https://travis-ci.com/alex-patterson-webdev/container-array.svg?branch=master)](https://travis-ci.com/alex-patterson-webdev/container-array)
[![codecov](https://codecov.io/gh/alex-patterson-webdev/container-array/branch/master/graph/badge.svg)](https://codecov.io/gh/alex-patterson-webdev/container-array)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alex-patterson-webdev/container-array/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alex-patterson-webdev/container-array/?branch=master)

# Arp\ContainerArray

## About

A simple PSR-11 compatible Dependency Injection Container.
 
## Installation

Installation via [Composer](https://getcomposer.org).

    require alex-patterson-webdev/container-array ^1
 
## Usage

Create a new instance of `Arp\ContainerArray\ArrayContainer` and pass the optional service configuration options.

    $config = [
        'aliases' => [
            //...
        ],
        'services' => [
            //...
        ],
        'factories' => [
            //...
        ],
        'factory_classes' => [
            //...
        ],
    ];
    $container = new ArrayContainer($config);
