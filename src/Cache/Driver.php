<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Psr\Log\LoggerAwareInterface,
    Traversable;

/**
 * A slightly modified version of SimpleCache Interface
 * Why not make a v2 with typehinting?
 */
interface Driver extends LoggerAwareInterface {

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool   true on success, false otherwise
     */
    public function clear(): bool;

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key The cache item key.
     *
     * @return bool  true on success, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     *
     * @return mixed|null null on cache miss
     */
    public function get(string $key);

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     *
     * @return Traversable An Iterator indexed by key => value.
     */
    public function getMultiple(iterable $keys): Traversable;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string    $key            The key of the item to store.
     * @param mixed     $value          The value of the item to store, must be serializable.
     * @param int       $expiry         The timestamp at which the item will expire (a value of 0 never expires).
     *
     * @return bool   true on success, false otherwise
     */
    public function set(string $key, $value, int $expiry = 0): bool;

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param array     $values     A list of key => value pairs for a multiple-set operation.
     * @param int       $expiry     The timestamp at which the item will expire (a value of 0 never expires).
     *
     * @return bool     true on success(even if object removed), false otherwise.
     *
     */
    public function setMultiple(array $values, int $expiry = 0): bool;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool true on success, false otherwise.
     */
    public function delete(string $key): bool;

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param array $keys A list of keys to be deleted.
     *
     * @return bool true on success, false otherwise.
     */
    public function deleteMultiple(array $keys): bool;
}
