<?php

declare(strict_types=1);

/**
 * Polyfills
 */

namespace React\Promise {

    interface PromiseInterface
    {

        public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null): static;
    }

}

namespace React\Cache {

    use React\Promise\PromiseInterface;

    interface CacheInterface
    {

        /**
         * Retrieves an item from the cache.
         *
         * @param string $key
         * @param mixed  $default Default value to return for cache miss or null if not given.
         * @return PromiseInterface<mixed>
         */
        public function get($key, $default = null): PromiseInterface;

        /**
         * Stores an item in the cache.
         *
         * @param string $key
         * @param mixed  $value
         * @param ?float $ttl
         * @return PromiseInterface<bool> Returns a promise which resolves to `true` on success or `false` on error
         */
        public function set($key, $value, $ttl = null): PromiseInterface;

        /**
         * Deletes an item from the cache.
         *
         * @param string $key
         * @return PromiseInterface<bool> Returns a promise which resolves to `true` on success or `false` on error
         */
        public function delete($key): PromiseInterface;

        /**
         * Retrieves multiple cache items by their unique keys.
         *
         *
         * @param string[] $keys A list of keys that can obtained in a single operation.
         * @param mixed $default Default value to return for keys that do not exist.
         * @return PromiseInterface<array> Returns a promise which resolves to an `array` of cached values
         */
        public function getMultiple(array $keys, $default = null): PromiseInterface;

        /**
         * Persists a set of key => value pairs in the cache, with an optional TTL.
         *
         * @param array  $values A list of key => value pairs for a multiple-set operation.
         * @param ?float $ttl    Optional. The TTL value of this item.
         * @return PromiseInterface<bool> Returns a promise which resolves to `true` on success or `false` on error
         */
        public function setMultiple(array $values, $ttl = null): PromiseInterface;

        /**
         * Deletes multiple cache items in a single operation.
         *
         * @param string[] $keys A list of string-based keys to be deleted.
         * @return PromiseInterface<bool> Returns a promise which resolves to `true` on success or `false` on error
         */
        public function deleteMultiple(array $keys): PromiseInterface;

        /**
         * Wipes clean the entire cache.
         *
         * @return PromiseInterface<bool> Returns a promise which resolves to `true` on success or `false` on error
         */
        public function clear(): PromiseInterface;

        /**
         * Determines whether an item is present in the cache.
         *
         * @param string $key The cache item key.
         * @return PromiseInterface<bool> Returns a promise which resolves to `true` on success or `false` on error
         */
        public function has($key): PromiseInterface;
    }

}

