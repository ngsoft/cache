<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Psr\Log\LoggerAwareInterface,
    Traversable;

/**
 * A slightly modified version of SimpleCache Interface
 */
interface Driver extends LoggerAwareInterface {

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
     * @return bool true if the process was successful, false otherwise.
     */
    public function invalidateNamespace(): bool;

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
     * @param string ...$listOfKeys A list of keys that can obtained in a single operation.
     *
     * @return Traversable An Iterator indexed by key => value.
     */
    public function getMultiple(string ...$listOfKeys): Traversable;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string    $key            The key of the item to store.
     * @param mixed     $value          The value of the item to store, must be serializable.
     * @param int       $lifeTime       The TTL to use, a value of 0 never expires, a negative value removes the values from the storage.
     *
     * @return bool   true on success, false otherwise
     */
    public function set(string $key, $value, int $lifeTime = 0): bool;

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable  $values     A list of key => value pairs for a multiple-set operation.
     * @param int       $lifeTime   The TTL to use, a value of 0 never expires, a negative value removes the values from the storage.
     *
     * @return bool     true on success(even if object removed), false otherwise.
     *
     */
    public function setMultiple(iterable $values, int $lifeTime = 0): bool;

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
     * @param string ...$listOfKeys A list of keys to be deleted.
     *
     * @return bool true on success, false otherwise.
     */
    public function deleteMultiple(string ...$listOfKeys): bool;
}
