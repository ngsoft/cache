<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException;
use NGSOFT\{
    Cache\CacheDriver, Cache\CacheException, Cache\CacheItem, Cache\CacheUtils, Cache\InvalidArgumentException, Cache\Key, Cache\Tag, Cache\TagList, Tools\FixedArray,
    Traits\LoggerAware
};
use Throwable,
    TypeError;

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
    public const MIN_INDEX_CAPACITY = 32;

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
    private const NAMESPACE_VERSION_KEY = 'NGSOFT_CACHE_DRIVER_NAMESPACE_VERSION[%s]';

    /**
     * A Reserved cache key that is used to check if at least one tag has been created in the current namespace
     * That can improve performances if no tags has been issued as it prevents cache hits on save and removal
     */
    private const CREATED_TAG_KEY = 'NGSOFT_CACHE_DRIVER_CREATED_TAG';

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
     * @var int|null
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
     * @var FixedArray|array
     */
    protected $expiries;

    /**
     * Used to index the currently loaded tags
     *
     * @var FixedArray|array
     */
    protected $loadedTags;

    /** @var bool|null */
    private $hasCreatedTags;

    /**
     * @param int $capacity Maximum capacity of the FixedArray used to contains the Tag, expiries of items that are already loaded (increases performances)
     */
    public function __construct(
            int $capacity = 0
    ) {
        // cannot be negative, cannot be less than 32 if defined
        $this->capacity = $capacity > 0 ? max(self::MIN_INDEX_CAPACITY, $capacity) : 0;
        $this->initialize();
    }

    /**
     * Resets the indexes and some params
     */
    final protected function initialize() {
        if ($this->capacity > 0) {
            $this->expiries = FixedArray::create($this->capacity);
            $this->loadedTags = FixedArray::create($this->capacity);
            $this->expiries->recursive = $this->loadedTags->recursive = false;
        } else $this->expiries = $this->loadedTags = [];
        $this->hasCreatedTags = null;
        $this->namespace_version = null;
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

    /** {@inheritdoc} */
    final public function fetchTag(string $tag): Tag {
        $cacheKey = $this->getStorageKey(sprintf(self::TAG_MODIFIER, $tag));
        $hKey = $this->getHashedKey($cacheKey);
        if (isset($this->loadedTags[$hKey])) return clone $this->loadedTags[$hKey];
        $tagItem = null;
        if ($this->hasCreatedTags()) $tagItem = $this->fetchValue($cacheKey, null);
        if (!($tagItem instanceof Tag)) $tagItem = new Tag($tag);
        $this->loadedTags[$hKey] = clone $tagItem;
        return $tagItem;
    }

    /** {@inheritdoc} */
    final public function saveTag(Tag ...$tags): bool {
        if (count($tags) == 0) return true;
        $r = true;
        $toRemove = $toSave = [];
        foreach ($tags as $tagItem) {
            $cacheKey = $this->getStorageKey(sprintf(self::TAG_MODIFIER, $tagItem->getLabel()));
            $hKey = $this->getHashedKey($cacheKey);
            unset($this->loadedTags[$hKey]);
            if (count($tagItem) == 0) $toRemove[] = $cacheKey;
            else $toSave[$cacheKey] = $tagItem;
        }
        if (count($toRemove) > 0) $r = $this->delete(...$toRemove);
        if (count($toSave) > 0) {
            if (!$this->hasCreatedTags()) $this->hasCreatedTags(true);
            //direct input as we don't need a cache item
            $r = $this->doSave($toSave) && $r;
        }

        if ($r === true) {
            foreach ($tags as $tagItem) $this->loadedTags[$this->getHashedKey($this->getStorageKey($tagItem->getLabel()))] = clone $tagItem;
        }
        return $r;
    }

    /** {@inheritdoc} */
    final public function deleteAll(): bool {
        $key = $this->getNamespaceKey();
        $version = $this->getNamespaceVersion() + 1;
        $this->initialize();
        if ($this->saveValue($key, $version)) {
            $this->namespace_version = $version;
            return true;
        }
        return false;
    }

    /** {@inheritdoc} */
    public function contains(string $key): bool {
        $hKey = $this->getHashedKey($this->getStorageKey($key));
        if (isset($this->expiries[$hKey])) {
            if ($this->isExpired($this->expiries[$hKey])) {
                unset($this->expiries[$hKey]);
                $this->delete($key);
                return false;
            }
            return true;
        }
        $item = $this->getItem($key);
        if ($item->isHit()) {
            $this->expiries[$hKey] = $item->getExpiration();
            return true;
        }
        return false;
    }

    /** {@inheritdoc} */
    final public function delete(string ...$keys): bool {
        if (empty($keys)) return true;
        $r = true;
        if ($this->hasCreatedTags()) {
            //we need to know which tags to remove
            $taglist = new TagList();
            $loadedTags = [];
            foreach ($this->fetch(...$keys) as $item) {
                if (count($item->getPreviousTags()) > 0) {
                    $key = $item->getKey();
                    foreach ($item->getPreviousTags() as $tagName) {
                        if (!isset($loadedTags[$tagName])) {
                            $loadedTags[$tagName] = $tagName;
                            $taglist->loadTag($this->fetchTag($tagName));
                        }
                        $taglist->remove($key, $tagName);
                    }
                }
            }
            if (count($loadedTags) > 0) {
                $tags = [];
                foreach ($loadedTags as $tagName) {
                    $tags[] = $taglist->getTag($tagName);
                }
                $r = $this->saveTag(...$tags);
            }
        }

        return $this->doDelete(... array_map(fn($k) => $this->getStorageKey($k), $keys)) && $r;
    }

    /** {@inheritdoc} */
    final public function fetch(string ...$keys) {
        if (empty($keys)) return;
        $nKeys = array_combine(array_map(fn($k) => $this->getStorageKey($k), $keys), $keys);
        $args = array_keys($nKeys);
        foreach ($this->doFetch(...$args) as $sKey => $value) {
            unset($this->expiries[$this->getHashedKey($sKey)]);
            $key = $nKeys[$sKey];
            if ($value instanceof CacheObject) {
                $item = $this->createItem($key, $value->value, $value->expiry, $value->tags);
                if ($item->isHit()) $this->expiries[$this->getHashedKey($sKey)] = $item->getExpiration();
                yield $key => $item;
            } else yield $key => $this->createItem($key);
        }
    }

    /** {@inheritdoc} */
    final public function save(CacheItem ...$items): bool {
        if (empty($items)) return true;
        $r = true;
        $toSave = $toRemove = $loadedTags = [];
        // compute tags to add and delete
        $taglist = new TagList();

        foreach ($items as $item) {
            $sKey = $this->getStorageKey($key = $item->getKey());
            if ($item->isHit()) {
                $expire = $item->getExpiration();
                $value = $item->get();
                if (
                        $this->hasCreatedTags() and
                        count($oldTags = $item->getPreviousTags()) > 0
                ) {
                    foreach ($oldTags as $tagName) {
                        if (!isset($loadedTags[$tagName])) {
                            $loadedTags[$tagName] = $tagName;
                            $taglist->loadTag($this->fetchTag($tagName));
                        }
                        $taglist->remove($key, $tagName);
                    }
                }
                if (count($tags = $item->getNewTags()) > 0) {
                    foreach ($tags as $tagName) {
                        if (!isset($loadedTags[$tagName])) {
                            $loadedTags[$tagName] = $tagName;
                            $taglist->loadTag($this->fetchTag($tagName));
                        }
                        $taglist->add($key, $tagName);
                    }
                }
                $toSave[$sKey] = new CacheObject($sKey, $value, $expire, $tags);
            } else $toRemove[] = $key;
        }
        if (count($toSave) > 0) {
            if (count($loadedTags) > 0) {
                $tagsTosave = [];
                foreach ($loadedTags as $tagName) {
                    $tagsTosave[] = $taglist->getTag($tagName);
                }
                $r = $this->saveTag(...$tagsTosave) && $r;
            }
            //we handle creation before removal as we already commited the tags
            $r = $this->doSave($toSave) && $r;
        }
        if (count($toRemove) > 0) {
            $r = $this->delete(...$toRemove) && $r;
        }
        return $r;
    }

    /** {@inheritdoc} */
    final public function clear(): bool {
        $this->initialize();
        return $this->doClear();
    }

    ////////////////////////////   Abstract Methods   ////////////////////////////

    /**
     * Deletes one or several cache entries.
     *
     * @param string ...$keys The namespaced keys to delete.
     * @return bool True if the items was successfully removed. False if there was an error.
     */
    abstract protected function doDelete(string ...$keys): bool;

    /**
     * Save Multiples entries using an array of keys and value pairs
     * @param array<string,CacheObject|mixed> $keysAndValues Namespaced(not hashed) key and values
     * @return bool true if 'all' entries were saved
     */
    abstract protected function doSave(array $keysAndValues): bool;

    /**
     * Fetches multiple entries from the cache
     *
     * @param string ...$keys A list of namespaced keys (not hashed) to fetch
     * @return \Traversable An iterator indexed by keys and a null result if not fetched
     */
    abstract protected function doFetch(string ...$keys);

    /**
     * Flushes all cache entries (globally).
     *
     * @return bool true if the cache entries were successfully flushed, false otherwise.
     */
    abstract protected function doClear(): bool;



    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Creates an array to serialize (or other methods)
     * to save into storage
     *
     * @param mixed $value
     * @param int $expire
     * @param array $tags
     * @return array
     */
    protected function buildItemToSave($value, int $expire, array $tags = []): array {
        try {
            $tagsToSave = [];
            foreach ($tags as $key) {
                $this->checkType($key, 'string', Key::class);
                if ($key instanceof Key) {
                    foreach ($key as $tagItem) {
                        $tagsToSave[$tagItem->getLabel()] = $tagItem->getLabel();
                    }
                } elseif (is_string($key)) $tagsToSave[$key] = $key;
            }
            $tagsToSave = array_values($tagsToSave);
            return [
                't' => $tagsToSave,
                'e' => $expire,
                'v' => $value
            ];
        } catch (TypeError $error) {
            throw new CacheException(sprintf('Invalid tag type. %s', $error->getMessage()));
        }
    }

    /**
     * Creates a cache item using decoded datas from buildItemToSave()
     *
     * @param CacheObject $obj
     * @return CacheItem
     */
    protected function createCacheItemFromSaved(CacheObject $obj): CacheItem {
        $tags = $value['t'] ?? [];
        $expire = $value['e'] ?? null;
        $v = $value['v'] ?? null;
        return $this->createItem($key, $v, $expire, $tags);
    }

    /**
     * Prevents Thowable inside classes __sleep or __serialize methods to interrupt operarations
     * @param mixed $value
     * @return string|null
     */
    protected function safeSerialize($value): ?string {

        if ($value === null) return null;
        try {
            $this->setErrorHandler();
            return \serialize($value);
        } catch (Throwable $ex) { return null; } finally { \restore_error_handler(); }
    }

    /**
     * Prevents Thowable inside classes __wakeup or __unserialize methods to interrupt operarations
     * Also the warning for wrong input
     * @param string $input
     * @return mixed|null
     */
    protected function safeUnserialize($input) {

        if (!is_string($input)) return null;
        try {
            $this->setErrorHandler();
            $result = \unserialize($input);
            //warning will be converted to ErrorException but we never know
            if (false === $result) {
                return null;
            }
            return $result;
        } catch (Throwable $ex) { return null; } finally { \restore_error_handler(); }
    }

    /**
     * Convenient Function used to convert php errors, warning, ... as ErrorException
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @staticvar Closure $handler
     * @return void
     */
    protected function setErrorHandler(): void {
        static $handler;
        if (!$handler) {
            $handler = static function ($type, $msg, $file, $line) {
                throw new ErrorException($msg, 0, $type, $file, $line);
            };
        }
        set_error_handler($handler);
    }

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
        foreach ($this->doFetch($key) as $value) {
            return $value !== null ? $value : $default;
        }
        return $default;
    }

    /**
     * Shortcut to save value directly to the cache
     *
     * @internal Do not uses Cache pool lifetime
     * @param string $key The cache Key
     * @param mixed $value The value to save
     * @return bool
     */
    protected function saveValue(string $key, $value): bool {
        return $value !== null ? $this->doSave([$key => $value]) : false;
    }

    /**
     * Get the cache key for the namespace
     * @return string
     */
    private function getNamespaceKey(): string {
        return sprintf(self::NAMESPACE_VERSION_KEY, $this->namepace);
    }

    /**
     * Get the Tag Creation status
     * @param bool|null $set Change the status
     * @return bool
     */
    protected function hasCreatedTags(bool $set = null): bool {
        if ($set !== null and $this->saveValue($this->getStorageKey(self::CREATED_TAG_KEY), $set)) {
            $this->hasCreatedTags = true;
        }
        if ($this->hasCreatedTags === null) {
            $this->hasCreatedTags = $this->fetchValue($this->getStorageKey(self::CREATED_TAG_KEY), false) === true;
        }
        return $this->hasCreatedTags;
    }

    /**
     * Get Current Namespace Version
     * @return int
     */
    private function getNamespaceVersion(): int {
        if ($this->namespace_version === null) {
            $key = $this->getNamespaceKey();
            if (is_int($val = $this->fetchValue($key))) $this->namespace_version = $val;
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
        return sprintf(self::NAMESPACE_MODIFIER, $this->getNamespace(), $key, $this->getNamespaceVersion());
    }

    /**
     * Get a 32 Chars hashed key
     *
     * @param string $key
     * @return string
     */
    final protected function getHashedKey(string $key): string {
        // classname added to prevent conflicts on similar drivers
        // MD5 as we need speed and some filesystems are limited in length
        return hash('MD5', static::class . $key);
    }

}

/**
 * A CacheObject
 */
class CacheObject {

    /** @var string */
    public $key;

    /** @var mixed */
    public $value;

    /** @var int|null */
    public $expiry = null;

    /** @var string[] */
    public $tags = [];

    /**
     * @param string $key The Namespaced key
     * @param mixed $value
     * @param int|null $expiry
     * @param array $tags
     */
    public function __construct(string $key, $value = null, int $expiry = null, array $tags = []) {

        $this->key = $key;
        $this->value = $value;
        $this->expiry = $expiry ?? 0;
        $this->tags = $tags;
    }

    /** {@inheritdoc} */
    public static function __set_state($data) {
        static $obj;
        if (!$obj) $obj = new static('key');
        $c = clone $obj;
        $c->key = $data['key'] ?? '';
        $c->value = $data['value'] ?? null;
        $c->expiry = $data['expiry'] ?? null;
        $c->tags = $data['tags'] ?? [];
        return $c;
    }

    /** {@inheritdoc} */
    public function __unserialize(array $data) {
        $this->key = $data['k'] ?? '';
        $this->value = $data['v'] ?? null;
        $this->expiry = $data['e'] ?? null;
        $this->tags = $data['t'] ?? [];
    }

    /** {@inheritdoc} */
    public function __serialize() {
        return [
            'k' => $this->key,
            'v' => $this->value,
            'e' => $this->expiry,
            't' => $this->tags
        ];
    }

}
