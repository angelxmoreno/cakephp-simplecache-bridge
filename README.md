# CakePHP SimpleCache Bridge

A bridge to convert CakePHP Cache to SimpleCache (PSR16)

[![Build Status](https://travis-ci.com/angelxmoreno/cakephp-simplecache-bridge.svg?branch=master)](https://travis-ci.com/angelxmoreno/cakephp-simplecache-bridge)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/e73c4fb3793649d18b005595fb4ee70d)](https://www.codacy.com/app/angelxmoreno/cakephp-simplecache-bridge?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=angelxmoreno/cakephp-simplecache-bridge&amp;utm_campaign=Badge_Grade)
[![Maintainability](https://api.codeclimate.com/v1/badges/5df91c50da39c722a010/maintainability)](https://codeclimate.com/github/angelxmoreno/cakephp-simplecache-bridge/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/5df91c50da39c722a010/test_coverage)](https://codeclimate.com/github/angelxmoreno/cakephp-simplecache-bridge/test_coverage)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)

## Why build this? 

In a few of my CakePHP apps I make use of 3rd party libraries that require a PSR-16 compatible cache engine. This bridge
allows me to reuse the Cache Engines and Cache configs already available within my CakePHP application and eliminates 
the need of having to build 2 different sets of Cache management libraries.

## Isn't this already in CakePHP 3.7 ?

No. CakePHP 3.7 brings with it a PSR-16 CacheEngine; meaning will be able to decorate a PSR-16 object to implement 
`Cake\Cache\CacheEngine` methods. This bridge is to go from `Cake\Cache\CacheEngine` to PSR-16, not the other way around.

## Examples

```php
Cache::config('short', [
    'className' => 'File',
    'duration' => '+1 hours',
    'path' => CACHE,
    'prefix' => 'cake_short_'
]);

$cache = new Bridge('short');

$cache->set('some_key', 'some value');
$value = $cache->get('some_key', 'some default');
$cache->set('some_key', 'some value', 300); //cached for 300 seconds instead of `+1 hours`

$interval = new \DateTimeInterval('P1Y'); // an interval of 1 year
$cache->set('some_key', 'some value', $interval); //cached for 1 year instead of `+1 hours`

```

## Requirements

- PHP >=5.6
- CakePHP >=3.0

## Installation

You can install this library using [composer](http://getcomposer.org).

The recommended way to install as a composer package:

```sh
composer require angelxmoreno/cakephp-simplecache-bridge
```

## Setup

Once you have a cahe configuration defined, you simple have to pass the config name when creating an instance of the Bridge
like so:
```php
Cache::config('short', [
    'className' => 'File',
    'duration' => '+1 hours',
    'path' => CACHE,
    'prefix' => 'cake_short_'
]);

$cache = new Bridge('short');
```

## Reporting Issues

If you have a problem with this library please open an issue on [GitHub](https://github.com/angelxmoreno/cakephp-simplecache-bridge/issues).

## License

This code is offered under an [MIT license](https://opensource.org/licenses/mit-license.php).
