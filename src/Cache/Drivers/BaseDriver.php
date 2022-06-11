<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException;
use NGSOFT\{
    Cache\CacheEntry, Cache\Interfaces\CacheDriver, Traits\StringableObject, Traits\Unserializable
};
use Psr\Log\LoggerAwareTrait,
    Stringable,
    Throwable,
    Traversable;

abstract class BaseDriver implements CacheDriver, Stringable
{

    use LoggerAwareTrait,
        StringableObject,
        Unserializable;

    public const KEY_EXPIRY = 0;
    public const KEY_VALUE = 1;
    public const KEY_TAGS = 2;

    protected int $defaultLifetime = 0;

    public function getIterator(): Traversable
    {
        yield $this;
    }

    /** {@inheritdoc} */
    public function purge(): void
    {
        // please extends on drivers that implements it
    }

    /** {@inheritdoc} */
    public function setDefaultLifetime(int $defaultLifetime): void
    {
        $this->defaultLifetime = max(0, $defaultLifetime);
    }

    /** {@inheritdoc} */
    public function increment(string $key, int $value = 1): int
    {

        $current = $this->get($key);

        if (is_int($current)) {
            $value += $current;
        }
        $this->set($key, $value, 0);
        return $value;
    }

    /** {@inheritdoc} */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, $value * -1);
    }

    /** {@inheritdoc} */
    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->getCacheEntry($key);
        if ($entry->isHit()) {
            return $entry->value;
        }
        return $default instanceof \Closure ? $default($this, $key) : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expiry
     * @param array $tags
     * @return bool
     */
    abstract protected function doSet(string $key, mixed $value, int $expiry, array $tags): bool;

    /** {@inheritdoc} */
    public function set(string $key, mixed $value, ?int $ttl = null, string|array $tags = []): bool
    {

        $tags = is_array($tags) ? $tags : [$tags];
        $expiry = $this->lifetimeToExpiry($ttl);

        if ($this->isExpired($expiry) || null === $value) {
            return $this->delete($key);
        }

        $result = $this->doSet($key, $value, $expiry, $tags);

        if (false === $result) {
            $this->delete($key);
            return false;
        }

        if ($this->isTag($key)) {
            return $result;
        }

        return $this->tag($key, $tags) && $result;
    }

    /** {@inheritdoc} */
    public function deleteMany(iterable $keys): bool
    {

        $result = true;
        foreach ($keys as $key) {
            $result = $this->delete($key) && $result;
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function getMany(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /** {@inheritdoc} */
    public function setMany(iterable $values, ?int $ttl = null, string|array $tags = []): bool
    {
        $result = true;
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl, $tags) && $result;
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function invalidateTag(string|iterable $tags): bool
    {

        if (!is_iterable($tags)) {
            $tags = [$tags];
        }

        $removed = [];

        foreach ($tags as $tagName) {


            $tagKey = sprintf(self::TAG_PREFIX, $tagName);
            $entry = $this->get($tagKey, []);

            if (empty($entry)) {
                continue;
            }

            foreach ($entry as $key) {
                $cacheEntry = $this->getCacheEntry($key);
                if (!in_array($tagName, $cacheEntry->tags)) {
                    continue;
                }
                $removed[$key] = $this->delete($key);
            }

            $removed[$tagKey] = $this->delete($tagKey);
        }
        return count($removed) > 0 && $this->every(fn($val) => $val, $removed);
    }

    protected function some(callable $callable, iterable $iterable): bool
    {

        foreach ($iterable as $key => $value) {
            if ($callable($value, $key, $iterable)) {
                return true;
            }
        }
        return false;
    }

    protected function every(callable $callable, iterable $iterable): bool
    {
        foreach ($iterable as $key => $value) {

            if (!$callable($value, $key, $iterable)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tag a specific entry with given tags
     *
     * @param string $key
     * @param string|string[] $tags
     * @return bool
     */
    protected function tag(string $key, string|iterable $tags): bool
    {
        if (!is_iterable($tags)) {
            $tags = [$tags];
        }

        $result = true;
        foreach ($tags as $tagName) {
            $tagKey = sprintf(self::TAG_PREFIX, $tagName);
            $entry = $this->get($tagKey, []);

            if (!isset($entry[$key])) {
                $entry[$key] = $key;
                $result = $this->set($tagKey, $entry, 0) && $result;
            }
        }

        return $result;
    }

    protected function isTag(string $key): bool
    {
        return 0 !== sscanf($key, self::TAG_PREFIX, $impl);
    }

    /**
     * Get a 32 Chars hashed key
     *
     * @param string $key
     * @return string
     */
    protected function getHashedKey(string $key): string
    {
        // classname added to prevent conflicts on similar drivers
        // MD5 as we need speed and some filesystems are limited in length
        return hash('MD5', static::class . $key);
    }

    /**
     * Convenience function to convert expiry into TTL
     * A TTL/expiry of 0 never expires
     *
     *
     * @param int $expiry
     * @return int the ttl a negative ttl is already expired
     */
    protected function expiryToLifetime(int $expiry): int
    {
        return
                $expiry !== 0 ?
                $expiry - time() :
                0;
    }

    /**
     * Converts ttl to expiry
     *
     * @param int|null $ttl
     * @return int
     */
    protected function lifetimeToExpiry(?int $ttl): int
    {

        if (null === $ttl) {
            return $this->defaultLifetime > 0 ? time() + $this->defaultLifetime : 0;
        }

        return $ttl === 0 ? 0 : time() + $ttl;
    }

    /**
     * Convenience function to check if item is expired status against current time
     * @param ?int $expiry
     * @return bool
     */
    protected function isExpired(?int $expiry): bool
    {
        if (null === $expiry) {
            return true;
        }

        return $expiry !== 0 && microtime(true) > $expiry;
    }

    /**
     * Convenient Function used to convert php errors, warning, ... as ErrorException
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @staticvar Closure $handler
     * @return void
     */
    protected function setErrorHandler(): void
    {
        static $handler;
        if (!$handler) {
            $handler = static function ($type, $msg, $file, $line) {
                throw new ErrorException($msg, 0, $type, $file, $line);
            };
        }
        set_error_handler($handler);
    }

    /**
     *
     * @param mixed $input
     * @return bool
     */
    protected function isSerialized(mixed $input): bool
    {
        return
                is_string($input) &&
                strpbrk($input[0] ?? '', 'idbsaO') !== false &&
                $input[1] === ':';
    }

    protected function serializeEntry(mixed $value): mixed
    {
        try {
            $this->setErrorHandler();
            return is_object($value) || is_array($value) ? \serialize($value) : $value;
        } catch (Throwable) { return null; } finally { \restore_error_handler(); }
    }

    protected function unserializeEntry(mixed $value): mixed
    {


        try {
            $this->setErrorHandler();
            //checks if serialized string
            if ($this->isSerialized($value)) {
                if ($value === 'b:0;') {
                    return false;
                }

                $result = \unserialize($value);
                return $result === false ? null : $result;
            }

            return $value;
        } catch (Throwable) { return null; } finally { \restore_error_handler(); }
    }

    protected function createEntry(mixed $value, int $expiry, array $tags = []): array
    {
        return [
            self::KEY_EXPIRY => $expiry,
            self::KEY_VALUE => $value,
            self::KEY_TAGS => $tags,
        ];
    }

    protected function createCacheEntry(string $key, mixed $entry): CacheEntry
    {

        $cacheEntry = CacheEntry::createEmpty($key);
        if (is_array($entry)) {
            if (
                    !$this->isExpired($entry[self::KEY_EXPIRY]) &&
                    null !== $entry[self::KEY_VALUE]
            ) {
                $cacheEntry->expiry = $entry[self::KEY_EXPIRY];
                $cacheEntry->value = $entry[self::KEY_VALUE];
                $cacheEntry->tags = $entry[self::KEY_TAGS] ?? [];
            }
        }
        return $cacheEntry;
    }

}
