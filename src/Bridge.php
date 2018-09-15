<?php

namespace Axm\CakePHPSimpleCacheBridge;

use Cake\Cache\Cache;
use Cake\Cache\CacheEngine;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Bridge
 * Adds a SimpleCache interface on a Cake\Cache\CacheEngine
 */
class Bridge implements CacheInterface
{
    const INVALID_KEY = 'Key provided must be a string';
    const INVALID_KEYS = 'Keys provided must an array or Traversable with string keys';
    const DURATION = 'duration';

    /**
     * @var string
     */
    protected $cache_config;

    /**
     * @var CacheEngine
     */
    protected $cache_engine;

    /**
     * @var int
     */
    protected $original_duration;

    /**
     * Bridge constructor.
     * @param string $cache_config
     */
    public function __construct($cache_config)
    {
        $this->cache_config = $cache_config;
        $this->cache_engine = Cache::engine($cache_config);
        $this->original_duration = $this->cache_engine->getConfig(self::DURATION);
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws InvalidArgumentException thrown if the $key is not a string
     */
    public function get($key, $default = null)
    {
        if ($this->keyIsInvalid($key)) {
            throw new InvalidArgumentException(self::INVALID_KEY);
        }

        $value = $this->getCacheEngineWithTTL()->read($key);

        return $value === false
            ? $default
            : $value;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent then uses the Cake\Cache\Engine's duration
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidArgumentException thrown if the $key is not a string
     */
    public function set($key, $value, $ttl = null)
    {
        if ($this->keyIsInvalid($key)) {
            throw new InvalidArgumentException(self::INVALID_KEY);
        }

        $engine = $this->getCacheEngineWithTTL($ttl);
        $success = $engine->write($key, $value);
        $this->restoreCacheEngineDuration($ttl);

        return $success;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException thrown if the $key is not a string
     */
    public function delete($key)
    {
        if ($this->keyIsInvalid($key)) {
            throw new InvalidArgumentException(self::INVALID_KEY);
        }

        return $this->getCacheEngineWithTTL()->delete($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return $this->getCacheEngineWithTTL()->clear(false);
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * NOTE: Cake\Cache\CacheEngine returns FALSE on cache misses making the use of $default unreliable
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws InvalidArgumentException thrown if $keys is neither an array nor a Traversable,or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        if ($this->keysAreInvalid($keys)) {
            throw new InvalidArgumentException(self::INVALID_KEYS);
        }

        $values = $this->getCacheEngineWithTTL()->readMany($this->keysAsArray($keys));

        foreach ($values as $key => $value) {
            $values[$key] = ($value === false)
                ? $default
                : $value;
        }

        return $values;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidArgumentException thrown if $values is neither an array nor a Traversable, or if any of the keys are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        if ($this->keysAreInvalid($values)) {
            throw new InvalidArgumentException('Values provided must an array or Traversable with string keys');
        }

        return $this->getCacheEngineWithTTL($ttl)->writeMany($this->keysAsArray($values));
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException thrown if $keys is neither an array nor a Traversable, or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        if ($this->keysAreInvalid($keys)) {
            throw new InvalidArgumentException(self::INVALID_KEYS);
        }

        return $this->getCacheEngineWithTTL()->deleteMany($this->keysAsArray($keys));
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * ALSO NOTE: Cake\Cache\CacheEngine returns FALSE on cache misses making this method unreliable
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws InvalidArgumentException thrown if the $key is not a string
     */
    public function has($key)
    {
        return (bool)$this->get($key, false);
    }

    protected function getCacheEngineWithTTL($ttl = null)
    {
        if (!is_null($ttl)) {
            $duration = $this->ttlToDuration($ttl);
            $this->cache_engine->setConfig(self::DURATION, $duration);
        }

        return $this->cache_engine;
    }

    protected function restoreCacheEngineDuration($ttl = null)
    {
        if (!is_null($ttl)) {
            $this->cache_engine->setConfig(self::DURATION, $this->original_duration);
        }
    }

    protected function ttlToDuration($ttl)
    {
        return ($ttl instanceof \DateInterval)
            ? $ttl->format('%s')
            : (int)$ttl;
    }

    protected function keyIsInvalid($key)
    {
        return !is_string($key);
    }

    protected function keysAreInvalid($keys)
    {
        if (!is_array($keys) && !is_object($keys) && !($keys instanceof \Traversable)) {
            return true;
        }

        foreach ($keys as $key) {
            if ($this->keyIsInvalid($key)) {
                return true;
            }
        }

        return false;
    }

    protected function keysAsArray($keys)
    {
        if (is_array($keys)) {
            return $keys;
        }

        return iterator_to_array($keys);
    }
}
