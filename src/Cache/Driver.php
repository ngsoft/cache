<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use JsonSerializable,
    Psr\Log\LoggerAwareInterface,
    Stringable,
    Traversable;

/**
 * A slightly modified version of SimpleCache Interface
 * Please make a v2 compatible PHP 7+ (not php 5.2)
 */
interface Driver extends LoggerAwareInterface, Stringable, JsonSerializable {

    /**
     * Set the default lifetime
     *
     * @param int $defaultLifetime
     * @return void
     */
    public function setDefaultLifetime(int $defaultLifetime): void;

    /**
     * Removes expired item entries if driver supports it
     *
     * @return bool true if operation was successful, false if not supported or error
     */
    public function purge(): bool;

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
     * @param array $keys A list of keys that can obtained in a single operation.
     *
     * @return Traversable An Iterator indexed by key => value.
     */
    public function getMultiple(array $keys): Traversable;

    /**
     * Persists data in the cache, uniquely referenced by a key
     *
     * @param string    $key            The key of the item to store.
     * @param mixed     $value          The value of the item to store, must be serializable.
     * @param int       $expiry         The timestamp at which the item will expire (a value of 0 never expires).
     *
     * @return bool   true on success(even if object removed), false otherwise
     */
    public function set(string $key, $value, int $expiry = 0): bool;

    /**
     * Persists a set of key => value pairs in the cache
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
