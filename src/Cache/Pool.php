<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\{
    Cache\CacheItemPoolInterface, Log\LoggerAwareInterface, SimpleCache\CacheInterface
};

/**
 * Cache Pool Interface
 */
interface Pool extends CacheInterface, CacheItemPoolInterface, TaggableCacheItemPoolInterface, LoggerAwareInterface {

    /**
     * Change the namespace for the current driver
     *
     * @param string $namespace The prefix to use
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
     * Invalidates current namespace items
     *
     * @return bool True if the items was successfully removed. False if there was an error.
     */
    public function deleteAll(): bool;

    /**
     * Used to do the Garbage Collection
     * Removes expired item entries if driver supports it
     *
     * @return bool true if operation was successful, false if not supported or error
     */
    public function removeExpired(): bool;
}
