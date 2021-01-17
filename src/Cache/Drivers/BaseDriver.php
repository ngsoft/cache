<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\CacheDriver, Cache\CacheItem, Cache\CacheUtils, Cache\InvalidArgumentException, Tools\FixedArray, Traits\LoggerAware
};

/**
 * That class defines the behaviour of all drivers
 * and implements the Tag system
 */
abstract class BaseDriver implements CacheDriver {

    use LoggerAware;

    use CacheUtils;

    /**
     * References the default metacache capacity
     */
    public const DEFAULT_INDEX_CAPACITY = 32;

    /**
     * Char codes used by hash method
     */
    protected const HASH_CARCODES = '0123456789abcdef';

    /**
     * Namespaces are used to prevent conflicts between differents applications that can use the same cache keys
     * A clear in a namespace will increment its version, so to remove the entries, use removeExpired()
     * Modifiers stands for Namespace[Key][NamespaceVersion]
     */
    protected const NAMESPACE_MODIFIER = '%s[%s][%u]';

    /**
     * A Reserved Cache Key that is used to retrieve and save the current namespace version
     * the %s stands for the namespace
     */
    protected const NAMESPACE_VERSION_KEY = 'NGSOFT_CACHE_DRIVER_NAMESPACE_VERSION[%s]';

    /**
     * Tags are saved using the cache pool, so they are also cache entries,
     * so to prevent conflicts with user provided key we have to add something to it
     */
    protected const TAG_MODIFIER = 'TAG!%s';

    /** @var string */
    protected $namepace = '';

    /**
     * Used to hold current namespace version
     * Changing the version invalidates cache items without removing them physically
     *
     * @var int
     */
    private $namespace_version;

    // A Fixed Array is an ArrayAccess object that limits
    // the number of values it can hold using its capacity,
    // Each time the fixed array is accessed (using isset not included) the internal pointer is updated and
    // older entries are removed (if capacity reached) to make place for the new

    /** @var int */
    protected $capacity;

    /**
     * Used to index expiries of already loaded items,
     *
     * @var FixedArray<string,int>
     */
    protected $expiries;

    /**
     * Used to index the currently loaded tags
     *
     * @var FixedArray<string,Tag|Key>
     */
    protected $loadedTags;

    /**
     * @param int $capacity Maximum capacity of the FixedArray used to contains the Tag, expiries of items that are already loaded (increases performances)
     */
    public function __construct(
            int $capacity = self::DEFAULT_INDEX_CAPACITY
    ) {
        // cannot be negative, cannot be less than 32
        $this->capacity = max(self::DEFAULT_INDEX_CAPACITY, $capacity);
        $this->initialize();
    }

    /**
     * Resets the indexes
     */
    final protected function initialize() {
        $this->expiries = FixedArray::create($this->capacity);
        $this->loadedTags = FixedArray::create($this->capacity);
    }

    ////////////////////////////   Interface   ////////////////////////////

    /** {@inheritdoc} */
    final public function getNamespace(): string {
        return $this->namepace;
    }

    /** {@inheritdoc} */
    final public function setNamespace(string $namespace): void {

        if (!preg_match(CacheDriver::VALID_NAMESPACE_REGEX, $namespace)) {
            throw new InvalidArgumentException(sprintf(
                                    'Illegal $namespace "%s" provided, valid characters are %s.',
                                    $namespace,
                                    CacheDriver::VALID_NAMESPACE_REGEX
            ));
        }
        $this->namepace = $namespace;
    }

    public function removeExpired(): bool {

    }

    public function fetchTag(string $tag): \NGSOFT\Cache\Tag {
        $cacheKey = sprintf(self::TAG_MODIFIER, $tag);
    }

    public function saveTag(\NGSOFT\Cache\Tag ...$tags): bool {

    }

    public function clear(): bool {

    }

    ////////////////////////////   Abstract Methods   ////////////////////////////

    /**
     *
     */
    abstract protected function doRemoveExpired(): bool;






    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Get an individual CacheItem
     * @param string $key
     * @return CacheItem
     */
    protected function getItem(string $key): CacheItem {
        foreach ($this->fetch($key) as $item) return $item;
        //will never get to that but PhanPluginAlwaysReturnMethod won't stops with it
        return $this->createItem($key);
    }

    /**
     * Shortcut to fetch value directly from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function fetchValue(string $key, $default = null) {
        $item = $this->getItem($key);
        return
                $item->isHit() ?
                $item->get() :
                $default;
    }

    /**
     * Shortcut to save value directly to the cache
     *
     * @internal Do not uses Cache pool lifetime
     * @param string $key The cache Key
     * @param mixed $value The value to save
     * @param int $ttl Lifetime for the item
     * @return bool
     */
    protected function saveValue(string $key, $value, int $ttl = 0): bool {
        return $this->save($this->createItem($key, $value, $ttl > 0 ? time() + $ttl : 0));
    }

    /**
     * Get the cache key for the namespace
     * @return string
     */
    private function getNamespaceKey(): string {
        return sprintf(self::NAMESPACE_VERSION_KEY, $this->namepace);
    }

    /**
     * Get Current Namespace Version
     * @return int
     */
    private function getNamespaceVersion(): int {
        if ($this->namespace_version === null) {
            $key = $this->getNamespaceKey();
            if (is_int($version = $this->fetchValue($key))) $this->namespace_version = $version;
            else $this->namespace_version = 1;
        }
        return $this->namespace_version;
    }

    /**
     * Get the namespaced key (This is the key used in the storage, not the one in the cache item)
     * Must be used when fetching and saving items
     *
     * @param string $key
     * @return string
     */
    final protected function getStorageKey(string $key): string {
        return sprintf(self::NAMESPACE_MODIFIER, $this->namepace, $key, $this->getNamespaceVersion());
    }

    /**
     * Get a 32 Chars hashed key
     * Replaces getStorageKey on some drivers that needs a word (eg: ArrayCache, FileSystem ...)
     *
     * @param string $key
     * @return string
     */
    final protected function getHashedKey(string $key): string {
        // classname added to prevent conflicts on similar drivers
        // MD5 as we need speed and some filesystems are limited in length
        return hash('MD5', static::class . $this->getStorageKey($key));
    }

}
