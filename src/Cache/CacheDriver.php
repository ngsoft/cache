<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Traversable;

interface CacheDriver extends \Psr\Log\LoggerAwareInterface, \Stringable
{

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
     * @return void
     */
    public function purge(): void;

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
     * @return CacheEntry
     */
    public function get(string $key): CacheEntry;

    /**
     * Fetches a value from the cache.
     *
     * @param string $key
     * @return mixed null on miss
     */
    public function getRaw(string $key): mixed;

    /**
     * Tag a key entry
     *
     * @param string $key
     * @param string|string[] $tag
     * @return bool
     */
    public function setTag(string $key, string|array $tag): bool;

    /**
     * Get tags for key entry
     *
     * @param string $key
     * @return string[]
     */
    public function getTags(string $key): iterable;

    /**
     * Remove tagged entries
     *
     * @param string|string[] $tag
     * @return bool
     */
    public function deleteTag(string|array $tag): bool;

    /**
     * Persists data in the cache, uniquely referenced by a key
     *
     * @param string    $key            The key of the item to store.
     * @param mixed     $value          The value of the item to store, must be serializable.
     * @param int       $expiry         The timestamp at which the item will expire (a value of 0 never expires).
     *
     * @return bool   true on success, false otherwise
     */
    public function set(string $key, mixed $value, int $expiry = 0): bool;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool true on success, false otherwise.
     */
    public function delete(string $key): bool;

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     *
     * @return Traversable An Iterator indexed by key => CacheEntry.
     */
    public function getMultiple(iterable $keys): Traversable;

    /**
     * Persists a set of key => value pairs in the cache
     *
     * @param iterable  $values     A list of key => value pairs for a multiple-set operation.
     * @param int       $expiry     The timestamp at which the item will expire (a value of 0 never expires, a null value uses the defaultLifetime).
     *
     * @return Traversable<string,bool>     true on success(even if object removed), false otherwise.
     *
     */
    public function setMultiple(iterable $values, int $expiry = 0): Traversable;

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of keys to be deleted.
     *
     * @return Traversable<string,bool> true on success, false otherwise.
     */
    public function deleteMultiple(iterable $keys): Traversable;
}
