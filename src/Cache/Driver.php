<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use React\Promise\PromiseInterface;

interface_exists(PromiseInterface::class);

/**
 * A slightly modified version of SimpleCache Interface using react/promise as engine
 */
interface Driver {

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return PromiseInterface<bool>   Resolves true on success, false otherwise
     */
    public function clear(): PromiseInterface;

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return PromiseInterface<bool>   Resolves true on success, false otherwise
     */
    public function has($key);

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     *
     * @return PromiseInterface Resolves the value on cache hit, rejects on cache miss
     */
    public function get(string $key): PromiseInterface;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string    $key            The key of the item to store.
     * @param mixed     $value          The value of the item to store, must be serializable.
     * @param int|null  $lifeTime       The TTL to use, a value of 0 never expires, a negative value removes the values from the storage.
     *
     * @return PromiseInterface<bool>   Resolves true on success, false otherwise
     */
    public function set(string $key, $value, int $lifeTime = 0): PromiseInterface;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return PromiseInterface Resolves true on success, false otherwise.
     */
    public function delete(string $key): PromiseInterface;

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param string    ...$listOfKeys A list of keys that can obtained in a single operation.
     *
     * @return Traversable<string,PromiseInterface> An Iterator indexed by key => PromiseInterface that resoves the value on success, else rejects.
     */
    public function getMultiple(string ...$listOfKeys): Traversable;

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable  $values     A list of key => value pairs for a multiple-set operation.
     * @param null|int  $lifeTime   The TTL to use, a value of 0 never expires, a negative value removes the values from the storage.
     *
     * @return PromiseInterface     Resolves true on success, false otherwise.
     *
     */
    public function setMultiple(iterable $values, int $lifeTime = 0): PromiseInterface;

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param string ...$listOfKeys A list of keys to be deleted.
     *
     * @return PromiseInterface     Resolves true on success, false otherwise.
     */
    public function deleteMultiple(string ...$listOfKeys): PromiseInterface;
}
