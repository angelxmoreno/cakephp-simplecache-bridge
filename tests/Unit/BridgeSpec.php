<?php

use Axm\CakePHPSimpleCacheBridge\Bridge;
use Axm\CakePHPSimpleCacheBridge\InvalidArgumentException;
use Cake\Cache\Cache;
use Cake\Cache\Engine\NullEngine;
use Kahlan\Plugin\Double;
use Psr\SimpleCache\CacheInterface as SimpleCache;

describe(Bridge::class, function () {
    beforeAll(function () {
        $this->cache_name = 'some_config_name';
        $this->cache_engine = Double::instance([
            'extends' => NullEngine::class
        ]);

        Cache::setConfig($this->cache_name, $this->cache_engine);

        $this->cache = new Bridge($this->cache_name);
    });

    it('returns an instance of SimpleCache (PSR-16)', function () {
        $cache = new Bridge($this->cache_name);

        expect($cache)->toBeAnInstanceOf(SimpleCache::class);
    });

    describe('->get()', function () {
        it('calls read on the engine', function () {
            $some_key = 'some_key';
            expect($this->cache_engine)->toReceive('read')->with($some_key);
            $this->cache->get($some_key);
        });
        context('when the key is invalid', function () {
            it('throws an ' . InvalidArgumentException::class, function () {
                $invalid_keys = [1, .1, true];

                foreach ($invalid_keys as $invalid_key) {
                    $closure = function () use ($invalid_key) {
                        $this->cache->get($invalid_key);
                    };

                    expect($closure)->toThrow(new InvalidArgumentException(Bridge::INVALID_KEY));
                }
            });
        });
        context('when a default is passed and is a cache miss', function () {
            it('returns the default value', function () {
                $some_value = 'some value';
                $value = $this->cache->get('some_key', $some_value);

                expect($value)->toBe($some_value);
            });
        });

        context('when a default is passed and is a cache is not a miss', function () {
            it('returns the cached value', function () {
                $some_value = 'some value';
                $some_key = 'some_key';

                allow($this->cache_engine)->toReceive('read')->with($some_key)->andReturn($some_value);
                expect($this->cache_engine)->toReceive('read')->with($some_key);

                $value = $this->cache->get($some_key, 'some other value');

                expect($value)->toBe($some_value);
            });
        });
    });

    describe('->getMultiple()', function () {
        it('calls readMany on the engine', function () {
            $keys = ['some_key'];
            expect($this->cache_engine)->toReceive('readMany')->with($keys);
            $this->cache->getMultiple($keys);
        });
        context('when the keys are invalid', function () {
            context('because keys are not iterable', function () {
                it('throws an ' . InvalidArgumentException::class, function () {
                    $closure = function () {
                        $this->cache->getMultiple(true);
                    };

                    expect($closure)->toThrow(new InvalidArgumentException(Bridge::INVALID_KEYS));
                });
            });

            context('because a key is not valid', function () {
                it('throws an ' . InvalidArgumentException::class, function () {
                    $closure = function () {
                        $this->cache->getMultiple(['some_key', true]);
                    };

                    expect($closure)->toThrow(new InvalidArgumentException(Bridge::INVALID_KEYS));
                });
            });
        });
        context('when a default is passed and is a cache miss', function () {
            it('returns the default value', function () {
                $default = 'some default value';
                $keys = ['some_key', 'some_other_key'];

                allow($this->cache_engine)->toReceive('readMany')->with($keys)->andReturn([
                    'some_key' => false,
                    'some_other_key' => 'some_value'
                ]);

                $value = $this->cache->getMultiple($keys, $default);

                expect($value)->toBe([
                    'some_key' => $default,
                    'some_other_key' => 'some_value'
                ]);
            });
        });

        context('when a default is passed and is a cache is not a miss', function () {
            it('returns the cached values', function () {
                $default = 'some default value';
                $keys = ['some_key', 'some_other_key'];
                $expected_values = [
                    'some_key' => 'some_value',
                    'some_other_key' => 'some_other_value',

                ];
                allow($this->cache_engine)->toReceive('readMany')->with($keys)->andReturn($expected_values);

                $values = $this->cache->getMultiple($keys, $default);

                expect($values)->toBe($expected_values);
            });
        });

        context('when keys is an Traversable', function () {
            it('returns the cached values', function () {
                $default = 'some default value';
                $keys = ['some_key', 'some_other_key'];
                $expected_values = [
                    'some_key' => 'some_value',
                    'some_other_key' => 'some_other_value',

                ];
                allow($this->cache_engine)->toReceive('readMany')->with($keys)->andReturn($expected_values);

                $values = $this->cache->getMultiple(new ArrayIterator($keys), $default);

                expect($values)->toBe($expected_values);
            });
        });
    });

    describe('->set()', function () {
        it('calls write on the engine', function () {
            $some_key = 'some_key';
            $some_value = 'some value';
            $ttl = 5;

            expect($this->cache_engine)->toReceive('write')->with($some_key, $some_value);

            $this->cache->set($some_key, $some_value, $ttl);
        });

        context('when the key is invalid', function () {
            it('throws an ' . InvalidArgumentException::class, function () {
                $invalid_keys = [1, .1, true];

                foreach ($invalid_keys as $invalid_key) {
                    $closure = function () use ($invalid_key) {
                        $this->cache->set($invalid_key, 'some value');
                    };

                    expect($closure)->toThrow(new InvalidArgumentException(Bridge::INVALID_KEY));
                }
            });
        });

        context('when a ttl is passed', function () {
            it('restores the original duration', function () {
                $some_key = 'some_key';
                $some_value = 'some value';
                $ttl = 5;

                allow($this->cache_engine)->toReceive('write')->with($some_key, $some_value)->andReturn(true);
                expect($this->cache_engine)->toReceive('setConfig')->with(Bridge::DURATION, $ttl);
                expect($this->cache_engine)->toReceive('write')->with($some_key, $some_value);

                $success = $this->cache->set($some_key, $some_value, $ttl);

                expect($success)->toBeTruthy();
            });
        });

        context('when a ttl is passed as an int', function () {
            it('sets the duration to the int', function () {
                $some_key = 'some_key';
                $some_value = 'some value';
                $ttl = 5;

                allow($this->cache_engine)->toReceive('write')->with($some_key, $some_value)->andReturn(true);
                expect($this->cache_engine)->toReceive('setConfig')->with(Bridge::DURATION, $ttl);
                expect($this->cache_engine)->toReceive('write')->with($some_key, $some_value);

                $success = $this->cache->set($some_key, $some_value, $ttl);

                expect($success)->toBeTruthy();
            });
        });
        context('when a ttl is passed as a DateInterval', function () {
            it('sets the duration to seconds of the DateInterval', function () {
                $some_key = 'some_key';
                $some_value = 'some value';
                $ttl = new DateInterval('PT2S');

                allow($this->cache_engine)->toReceive('write')->with($some_key, $some_value)->andReturn(true);
                expect($this->cache_engine)->toReceive('setConfig')->with(Bridge::DURATION, $ttl->format('%s'));
                expect($this->cache_engine)->toReceive('write')->with($some_key, $some_value);

                $success = $this->cache->set($some_key, $some_value, $ttl);

                expect($success)->toBeTruthy();
            });
        });
    });

    describe('->clear()', function () {
        it('calls clear on the engine', function () {
            expect($this->cache_engine)->toReceive('clear');
            $this->cache->clear();
        });
    });

    describe('->has()', function () {
        it('calls read on the engine', function () {
            $some_key = 'some_key';
            expect($this->cache_engine)->toReceive('read')->with($some_key);
            $this->cache->has($some_key);
        });
        context('when the key exists', function () {
            it('returns true', function () {
                $some_key = 'some_key';
                $some_value = 'some value';

                allow($this->cache_engine)->toReceive('read')->with($some_key)->andReturn($some_value);

                $exists = $this->cache->get($some_key);

                expect($exists)->toBeTruthy();
            });
        });

        context('when the key does not exist', function () {
            it('returns false', function () {
                $some_key = 'some_key';

                allow($this->cache_engine)->toReceive('read')->with($some_key)->andReturn(false);

                $exists = $this->cache->get($some_key);

                expect($exists)->toBeFalsy();
            });
        });
    });

    describe('->delete()', function () {
        it('calls delete on the engine', function () {
            $some_key = 'some_key';
            expect($this->cache_engine)->toReceive('delete')->with($some_key);
            $this->cache->delete($some_key);
        });
        context('when the key is invalid', function () {
            it('throws an ' . InvalidArgumentException::class, function () {
                $invalid_keys = [1, .1, true];

                foreach ($invalid_keys as $invalid_key) {
                    $closure = function () use ($invalid_key) {
                        $this->cache->delete($invalid_key);
                    };

                    expect($closure)->toThrow(new InvalidArgumentException(Bridge::INVALID_KEY));
                }
            });
        });
    });
});
