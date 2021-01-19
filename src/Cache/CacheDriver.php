<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Psr\Log\LoggerAwareInterface,
    Traversable;

/**
 * The Cache driver
 *   Does not handle keys/tags names verifications (the cache pool must do that)
 *   You can interact with it directly but the fetched/saved raw datas won't be compatible with the cache pool (CacheObject)
 *   Note that this is a multioperation driver interface.
 *
 */
interface CacheDriver extends LoggerAwareInterface {

    /**
     * Change the namespace for the current instance
     *   A namespace is a modifier assigned to the key
     *
     * @param string $namespace The prefix to use
     * @throws InvalidArgumentException if the namespace is invalid: '{}()/\@:' are found.
     * @return void
     */
    public function setNamespace(string $namespace): void;

    /**
     * Get the currently assigned namespace
     *
     * @return string
     */
    public function getNamespace(): string;

    /**
     * Invalidates current namespace items, increasing the namespace version.
     *
     * @return bool True if the items was successfully removed. False if there was an error.
     */
    public function invalidateAll(): bool;

    /**
     * Used to do the Garbage Collection
     * Removes expired item entries if driver supports it
     *
     * @return bool true if operation was successful, false if not supported or error
     */
    public function purge(): bool;

    /**
     * Confirms if the cache contains specified cache key.
     *
     * @param string $key The key for which to check existence.
     * @return bool true if item exists in the cache, false otherwise.
     */
    public function contains(string $key): bool;

    /**
     * Fetches multiple entries from the cache.
     *
     * @param string ...$keys The keys to fetch
     * @return Traversable A traversable indexed by keys null values must be issued on cache miss
     */
    public function fetch(string ...$keys): Traversable;

    /**
     * Persists a cache item(s) immediately.
     * If the cache item is expired it must be removed
     *
     * @param array $keysAndValues The cache item(s) to save.
     * @param int $expiry the timestamp at which the item expires (it is not a TTL, its an abolute number)
     * @return bool True if the items were successfully persisted/removed. False if there was an error.
     */
    public function save(array $keysAndValues, int $expiry = 0): bool;

    /**
     * Deletes one or several cache entries.
     *
     * @param string ...$keys The keys to delete.
     * @return bool True if the items was successfully removed. False if there was an error.
     */
    public function delete(string ...$keys): bool;

    /**
     * Flushes all cache entries (globally).
     *
     * @return bool true if the cache entries were successfully flushed, false otherwise.
     */
    public function clear(): bool;
}
