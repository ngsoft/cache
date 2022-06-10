<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Interfaces;

use IteratorAggregate,
    NGSOFT\Cache\CacheEntry,
    Psr\Log\LoggerAwareInterface;

interface CacheDriver extends IteratorAggregate, LoggerAwareInterface
{

    public const TAGGED_KEY_PREFIX = 'TAGS_FOR[%s]';
    public const TAG_PREFIX = 'TAG[%s]';

    /**
     * set the default ttl
     *
     * @param int $defaultLifetime
     * @return void
     */
    public function setDefaultLifetime(int $defaultLifetime): void;

    /**
     * Wipe clean the entire cache
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Removes expired item entries if supported
     *
     * @return void
     */
    public function purge(): void;

    ////////////////////////////   Single operations   ////////////////////////////

    /**
     * Determines if item is in the cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get cache entry containing metadata
     *
     * @param string $key
     * @return CacheEntry
     */
    public function getCacheEntry(string $key): CacheEntry;

    /**
     * Fetches an item from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persists data in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl a value of 0 never expires, a null value uses the default value set in the driver
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Increments a cache entry
     *
     * @param string $key
     * @param int $value
     * @return int new value
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * Decrement a cache entry
     *
     * @param string $key
     * @param int $value
     * @return int new value
     */
    public function decrement(string $key, int $value = 1): int;

    /**
     * Delete an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    ////////////////////////////   Multioperations   ////////////////////////////

    /**
     * Obtains multiple cache items
     *
     * @param iterable $keys
     * @param mixed $default
     * @return iterable<string, mixed>
     */
    public function getMany(iterable $keys, mixed $default = null): iterable;

    /**
     * Persist a set of key => value pairs in the cache
     *
     * @param iterable $values
     * @param ?int $ttl
     * @return iterable<string, bool>
     */
    public function setMany(iterable $values, ?int $ttl = null): iterable;

    /**
     * Deletes multiple cache items
     *
     * @param iterable $keys
     * @return iterable<string, bool>
     */
    public function deleteMany(iterable $keys): iterable;

    ////////////////////////////   Tag Support   ////////////////////////////

    /**
     * Tag a specific entry with given tags
     *
     * @param string $key
     * @param string|array $tags
     * @return bool
     */
    public function tag(string $key, string|array $tags): bool;

    /**
     * Removes tags for a specific entry
     *
     * @param string $key
     * @return bool
     */
    public function clearTags(string $key): bool;

    /**
     * Gat tags assigned to a specific entry
     *
     * @param string $key
     * @return array
     */
    public function getTags(string $key): array;

    /**
     * Removes entry that have the specified tags
     *
     * @param string|string[] $tags
     * @return bool
     */
    public function invalidateTag(string|iterable $tags): bool;

    /**
     * Get list of entries that have the specified tag
     *
     * @param string|string[] $tags
     * @return iterable<string, string> indexed by tag => key
     */
    public function getTagged(string|iterable $tags): iterable;
}
