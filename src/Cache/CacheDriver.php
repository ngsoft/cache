<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use JsonSerializable,
    Psr\Log\LoggerAwareInterface,
    Stringable,
    Traversable;

/**
 * The Cache driver
 * Does not handle keys/tags names verifications (the cache pool must do that)
 */
interface CacheDriver extends LoggerAwareInterface, Stringable, JsonSerializable {

    /**
     * Valid namespace can be an empty string or a word beginning with a letter
     * and containing [a-zA-Z0-9_-.], other characters are forbidden
     */
    public const VALID_NAMESPACE_REGEX = '/^(|[a-zA-Z][\w\-\.]+)$/';

    /**
     * Change the namespace for the current instance
     * A namespace is a prefix assigned to the key
     *
     * @param string $namespace The prefix to use
     * @throws InvalidArgumentException if the namespace is invalid
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
     * Used to do the Garbage Collection
     * Removes expired item entries if driver supports it
     *
     * @return bool true if operation was successful, false if not supported or error
     */
    public function removeExpired(): bool;

    /**
     * Confirms if the cache contains specified cache key.
     *
     * @param string $key The key for which to check existence.
     * @return bool true if item exists in the cache, false otherwise.
     */
    public function contains(string $key): bool;

    /**
     * Fetches multiple entries from the cache  (Tag entries must be included).
     *
     * @param string ...$keys The keys to fetch
     * @return Traversable|CacheItem[] A traversable indexed by keys Empty items must be issued on cache miss
     */
    public function fetch(string ...$keys);

    /**
     * Get a Tag object containing current assigned keys
     *
     * @param string $tag the tag label
     * @return Tag
     */
    public function fetchTag(string $tag): Tag;

    /**
     * Persists a cache Tag(s) immediately
     *
     * @param Tag ...$tags If tag object does not contains keys it must be removed.
     * @return bool True if the items were successfully persisted/removed. False if there was an error.
     */
    public function saveTag(Tag ...$tags): bool;

    /**
     * Persists a cache item(s) immediately (Tag entries must be included).
     * If the cache item is expired it must be removed
     *
     * @param CacheItem ...$items The cache item(s) to save.
     * @return bool True if the items were successfully persisted/removed. False if there was an error.
     */
    public function save(CacheItem ...$items): bool;

    /**
     * Deletes one or several cache entries.
     *
     * @param string ...$keys The keys to delete.
     * @return bool True if the items was successfully removed. False if there was an error.
     */
    public function delete(string ...$keys): bool;

    /**
     * Invalidates current namespace items
     *
     * @return bool True if the items was successfully removed. False if there was an error.
     */
    public function deleteAll(): bool;

    /**
     * Flushes all cache entries (globally).
     *
     * @return bool true if the cache entries were successfully flushed, false otherwise.
     */
    public function clear(): bool;
}
