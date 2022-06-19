<?php

declare(strict_types=1);

namespace Doctrine\Common\Cache;

/**
 * Interface for cache drivers.
 *
 * @link   www.doctrine-project.org
 */
interface Cache
{

    public const STATS_HITS = 'hits';
    public const STATS_MISSES = 'misses';
    public const STATS_UPTIME = 'uptime';
    public const STATS_MEMORY_USAGE = 'memory_usage';
    public const STATS_MEMORY_AVAILABLE = 'memory_available';

    /**
     * Only for backward compatibility (may be removed in next major release)
     *
     * @deprecated
     */
    public const STATS_MEMORY_AVAILIABLE = 'memory_available';

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch($id);

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function contains($id);

    /**
     * Puts data into the cache.
     *
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * @param string $id       The cache id.
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The lifetime in number of seconds for this cache entry.
     *                         If zero (the default), the entry never expires (although it may be deleted from the cache
     *                         to make place for other entries).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save($id, $data, $lifeTime = 0);

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     *              Deleting a non-existing entry is considered successful.
     */
    public function delete($id);

    /**
     * Retrieves cached information from the data store.
     *
     * The server's statistics array has the following values:
     *
     * - <b>hits</b>
     * Number of keys that have been requested and found present.
     *
     * - <b>misses</b>
     * Number of items that have been requested and not found.
     *
     * - <b>uptime</b>
     * Time that the server is running.
     *
     * - <b>memory_usage</b>
     * Memory used by this server to store items.
     *
     * - <b>memory_available</b>
     * Memory allowed to use for storage.
     *
     * @return mixed[]|null An associative array with server's statistics if available, NULL otherwise.
     */
    public function getStats();
}

/**
 * Interface for cache that can be flushed.
 *
 * Intended to be used for partial clearing of a cache namespace. For a more
 * global "flushing", see {@see FlushableCache}.
 *
 * @link   www.doctrine-project.org
 */
interface ClearableCache
{

    /**
     * Deletes all cache entries in the current cache namespace.
     *
     * @return bool TRUE if the cache entries were successfully deleted, FALSE otherwise.
     */
    public function deleteAll();
}

/**
 * Interface for cache that can be flushed.
 *
 * @link   www.doctrine-project.org
 */
interface FlushableCache
{

    /**
     * Flushes all cache entries, globally.
     *
     * @return bool TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll();
}

/**
 * Interface for cache drivers that allows to put many items at once.
 *
 * @deprecated
 *
 * @link   www.doctrine-project.org
 */
interface MultiDeleteCache
{

    /**
     * Deletes several cache entries.
     *
     * @param string[] $keys Array of keys to delete from cache
     *
     * @return bool TRUE if the operation was successful, FALSE if it wasn't.
     */
    public function deleteMultiple(array $keys);
}

/**
 * Interface for cache drivers that allows to get many items at once.
 *
 * @deprecated
 *
 * @link   www.doctrine-project.org
 */
interface MultiGetCache
{

    /**
     * Returns an associative array of values for keys is found in cache.
     *
     * @param string[] $keys Array of keys to retrieve from cache
     *
     * @return mixed[] Array of retrieved values, indexed by the specified keys.
     *                 Values that couldn't be retrieved are not contained in this array.
     */
    public function fetchMultiple(array $keys);
}

/**
 * Interface for cache drivers that allows to put many items at once.
 *
 * @deprecated
 *
 * @link   www.doctrine-project.org
 */
interface MultiPutCache
{

    /**
     * Returns a boolean value indicating if the operation succeeded.
     *
     * @param mixed[] $keysAndValues Array of keys and values to save in cache
     * @param int     $lifetime      The lifetime. If != 0, sets a specific lifetime for these
     *                               cache entries (0 => infinite lifeTime).
     *
     * @return bool TRUE if the operation was successful, FALSE if it wasn't.
     */
    public function saveMultiple(array $keysAndValues, $lifetime = 0);
}

/**
 * Interface for cache drivers that supports multiple items manipulation.
 *
 * @link   www.doctrine-project.org
 */
interface MultiOperationCache extends MultiGetCache, MultiDeleteCache, MultiPutCache
{

}

abstract class CacheProvider implements Cache, FlushableCache, ClearableCache, MultiOperationCache
{

    public const DOCTRINE_NAMESPACE_CACHEKEY = 'DoctrineNamespaceCacheKey[%s]';

    /**
     * Sets the namespace to prefix all cache ids with.
     *
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace($namespace)
    {

    }

    /**
     * Retrieves the namespace that prefixes all cache ids.
     *
     * @return string
     */
    public function getNamespace()
    {

    }

    /**
     * Default implementation of doFetchMultiple. Each driver that supports multi-get should owerwrite it.
     *
     * @param string[] $keys Array of keys to retrieve from cache
     *
     * @return mixed[] Array of values retrieved for the given keys.
     */
    protected function doFetchMultiple(array $keys)
    {

    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed|false The cached data or FALSE, if no cache entry exists for the given id.
     */
    abstract protected function doFetch($id);

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    abstract protected function doContains($id);

    /**
     * Default implementation of doSaveMultiple. Each driver that supports multi-put should override it.
     *
     * @param mixed[] $keysAndValues Array of keys and values to save in cache
     * @param int     $lifetime      The lifetime. If != 0, sets a specific lifetime for these
     *                               cache entries (0 => infinite lifeTime).
     *
     * @return bool TRUE if the operation was successful, FALSE if it wasn't.
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {

    }

    /**
     * Puts data into the cache.
     *
     * @param string $id       The cache id.
     * @param string $data     The cache entry/data.
     * @param int    $lifeTime The lifetime. If != 0, sets a specific lifetime for this
     *                           cache entry (0 => infinite lifeTime).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    abstract protected function doSave($id, $data, $lifeTime = 0);

    /**
     * Default implementation of doDeleteMultiple. Each driver that supports multi-delete should override it.
     *
     * @param string[] $keys Array of keys to delete from cache
     *
     * @return bool TRUE if the operation was successful, FALSE if it wasn't
     */
    protected function doDeleteMultiple(array $keys)
    {

    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    abstract protected function doDelete($id);

    /**
     * Flushes all cache entries.
     *
     * @return bool TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    abstract protected function doFlush();

    /**
     * Retrieves cached information from the data store.
     *
     * @return mixed[]|null An associative array with server's statistics if available, NULL otherwise.
     */
    abstract protected function doGetStats();
}
