<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use ErrorException,
    JsonSerializable;
use NGSOFT\Traits\{
    LoggerAware, Unserializable
};
use Stringable,
    Throwable,
    Traversable;

/**
 * That class defines the behaviour of all drivers
 *
 * Inspired by doctrine CacheProvider
 * @link https://github.com/doctrine/cache
 *
 */
abstract class BaseDriver implements Stringable, JsonSerializable {

    use LoggerAware;
    use CacheUtils;
    use Unserializable;

    /**
     * Char codes used by hash method
     */
    protected const HASH_CHARCODES = '0123456789abcdef';

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

    /** @var string */
    protected $namespace = '';

    /**
     * Used to hold current namespace version
     * Changing the version invalidates cache items without removing them physically
     *
     * @var int|null
     */
    private $namespace_version = null;

    ////////////////////////////   Interface   ////////////////////////////

    /** {@inheritdoc} */
    final public function getNamespace(): string {
        return $this->namespace;
    }

    /** {@inheritdoc} */
    final public function setNamespace(string $namespace): void {
        if (!empty($namespace) and (false !== strpbrk($namespace, '{}()/\@:'))) {
            throw new InvalidArgumentException(sprintf('Cache namespace "%s" contains reserved characters "%s".', $namespace, '{}()/\@:'));
        }
        $this->namespace = $namespace;
    }

    /** {@inheritdoc} */
    final public function deleteAll(): bool {
        $key = $this->getNamespaceKey();
        $version = $this->getNamespaceVersion() + 1;
        if ($this->saveOne($key, $version)) {
            $this->namespace_version = $version;
            return true;
        }
        return false;
    }

    ////////////////////////////   Namespace Helpers   ////////////////////////////

    /** {@inheritdoc} */
    final public function clear(): bool {
        $this->namespace_version = null;
        return $this->doClear();
    }

    /** {@inheritdoc} */
    final public function contains(string $key): bool {
        return $this->doContains($this->getStorageKey($key));
    }

    /** {@inheritdoc} */
    final public function delete(string ...$keys): bool {
        if (empty($keys)) return true;
        return $this->doDelete(...array_map(fn($k) => $this->getStorageKey($k), $keys));
    }

    /** {@inheritdoc} */
    final public function save(array $keysAndValues, int $expiry = 0): bool {
        if (empty($keysAndValues)) return true;
        if ($this->isExpired($expiry)) return $this->delete(...array_keys($keysAndValues));
        return $this->doSave(array_combine(array_map(fn($k) => $this->getStorageKey($k), array_keys($keysAndValues)), array_values($keysAndValues)), $expiry);
    }

    /** {@inheritdoc} */
    final public function fetch(string ...$keys): Traversable {
        if (empty($keys)) return;
        $keysToFetch = array_map(fn($k) => $this->getStorageKey($k), $keys);
        $assoc = array_combine($keysToFetch, $keys);
        foreach ($this->doFetch(...$keysToFetch) as $fetchedKey => $value) {
            yield $assoc[$fetchedKey] => $value;
        }
    }

    ////////////////////////////   Abstract Methods   ////////////////////////////

    /**
     * Confirms if the cache contains specified cache key.
     *
     * @param string $key The key for which to check existence.
     * @return bool true if item exists in the cache, false otherwise.
     */
    abstract protected function doContains(string $key): bool;

    /**
     * Deletes one or several cache entries.
     *
     * @param string ...$keys The namespaced keys to delete.
     * @return bool True if the items was successfully removed. False if there was an error.
     */
    abstract protected function doDelete(string ...$keys): bool;

    /**
     * Save Multiples entries using an array of keys and value pairs
     *
     * @param array<string,CacheObject|mixed> $keysAndValues Namespaced(not hashed) key and values
     * @param int $expiry the timestamp at which the item expires
     * @return bool true if 'all' entries were saved
     */
    abstract protected function doSave(array $keysAndValues, int $expiry = 0): bool;

    /**
     * Fetches multiple entries from the cache
     *
     * @param string ...$keys A list of namespaced keys (not hashed) to fetch
     * @return Traversable An iterator indexed by keys and a null result if not fetched
     */
    abstract protected function doFetch(string ...$keys): Traversable;

    /**
     * Flushes all cache entries (globally).
     *
     * @return bool true if the cache entries were successfully flushed, false otherwise.
     */
    abstract protected function doClear(): bool;



    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Prevents Thowable inside classes __sleep or __serialize methods to interrupt operarations
     *
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
     *
     * @param string $input
     * @return mixed|null
     */
    protected function safeUnserialize($input) {

        if (!is_string($input)) return null;
        // prevents cache miss
        switch ($input) {
            case 'b:1;':
                return true;
            case 'b:0;':
                return false;
        }
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
     * Shortcut to fetch value directly from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function fetchOne(string $key, $default = null) {
        foreach ($this->doFetch($key) as $value) {
            return $value !== null ? $value : $default;
        }
        return $default;
    }

    /**
     * Shortcut to save value directly to the cache
     *
     * @param string $key The cache Key
     * @param mixed $value The value to save
     * @param int $expiry Expiration Timestamp
     * @return bool true if item succesfully saved/removed
     */
    protected function saveOne(string $key, $value, int $expiry = 0): bool {
        $this->doCheckValue($value);
        return
                $value !== null and
                !$this->isExpired($expiry) ?
                $this->doSave([$key => $value], $expiry) :
                $this->doDelete($key);
    }

    /**
     * Get the cache key for the namespace
     * @return string
     */
    private function getNamespaceKey(): string {
        return sprintf(self::NAMESPACE_VERSION_KEY, $this->getNamespace());
    }

    /**
     * Get Current Namespace Version
     * @return int
     */
    private function getNamespaceVersion(): int {
        if ($this->namespace_version === null) {
            $key = $this->getNamespaceKey();
            if (is_int($val = $this->fetchOne($key))) $this->namespace_version = $val;
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
