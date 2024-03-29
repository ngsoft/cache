<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Closure;
use NGSOFT\{
    Cache\CacheEntry, Cache\Interfaces\CacheDriver, Cache\Interfaces\TaggableCacheItem, Cache\Utils\Toolkit, Traits\StringableObject, Traits\Unserializable
};
use Psr\Log\LoggerAwareTrait,
    Stringable,
    Throwable,
    Traversable;
use function NGSOFT\Tools\every;

abstract class BaseDriver implements CacheDriver, Stringable
{

    use LoggerAwareTrait,
        StringableObject,
        Unserializable,
        Toolkit;

    protected const KEY_EXPIRY = TaggableCacheItem::METADATA_EXPIRY;
    protected const KEY_VALUE = TaggableCacheItem::METADATA_VALUE;
    protected const KEY_TAGS = TaggableCacheItem::METADATA_TAGS;
    protected const TAG_PREFIX = 'TAG[%s]';

    /**
     * If you are not sure which ttl the drivers implements
     * you can use it as defaultLifetime
     */
    protected const LIFETIME_5YEARS = 157784760;

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

    /**
     * {@inheritdoc}
     * @phan-suppress PhanSuspiciousValueComparison
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $entry = $this->getCacheEntry($key);
        } catch (Throwable) {
            $entry = CacheEntry::createEmpty($key);
        }

        if ($entry->isHit()) {
            return $entry->value;
        }

        if ($default instanceof Closure) {
            $save = true;

            $value = $default($save);
            if ($save === true && ! is_null($value)) {
                $this->set($key, $value);
            }
            return $value;
        }

        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param ?int $ttl
     * @param array $tags
     * @return bool
     */
    abstract protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool;

    /** {@inheritdoc} */
    public function set(string $key, mixed $value, ?int $ttl = null, string|array $tags = []): bool
    {

        $tags = is_array($tags) ? $tags : [$tags];

        if ($value === null || $ttl < 0) {
            return $this->delete($key);
        }


        try {
            $result = $this->doSet($key, $value, $ttl, array_values($tags));
        } catch (Throwable) {
            $result = false;
        }


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
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function setMany(iterable $values, ?int $ttl = null, string|array $tags = []): bool
    {
        $result = true;
        foreach ($values as $key => $value) {
            if ( ! $this->set($key, $value, $ttl, $tags)) {
                $result = false;
            }
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function invalidateTag(string|iterable $tags): bool
    {

        if ( ! is_iterable($tags)) {
            $tags = [$tags];
        }

        $removed = [];

        foreach ($tags as $tagName) {


            $tagKey = sprintf(self::TAG_PREFIX, $tagName);
            $entry = $this->get($tagKey);

            if (empty($entry)) {
                continue;
            }

            foreach ($entry as $key) {
                $cacheEntry = $this->getCacheEntry($key);
                if ( ! in_array($tagName, $cacheEntry->tags)) {
                    continue;
                }
                $removed[$key] = $this->delete($key);
            }

            $removed[$tagKey] = $this->delete($tagKey);
        }
        return count($removed) > 0 && every(fn($val) => $val, $removed);
    }

    /**
     * Creates a tag entry into the cache that points to specific key(s)
     *
     * @param string $key
     * @param string|string[] $tags
     * @return bool
     */
    protected function tag(string $key, string|iterable $tags): bool
    {
        if ( ! is_iterable($tags)) {
            $tags = [$tags];
        }

        $result = true;
        foreach ($tags as $tagName) {
            $tagKey = sprintf(self::TAG_PREFIX, $tagName);
            $entry = $this->get($tagKey, []);

            if ( ! isset($entry[$key])) {
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

    protected function getLifetime(?int $ttl): int
    {
        return $ttl ?? $this->defaultLifetime;
    }

    /**
     * Converts ttl to expiry
     *
     * @param int|null $ttl
     * @return int
     */
    protected function lifetimeToExpiry(?int $ttl): int
    {
        $ttl = $this->getLifetime($ttl);
        return $ttl !== 0 ? time() + $ttl : $ttl;
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
                    ! $this->isExpired($entry[self::KEY_EXPIRY]) &&
                    null !== $entry[self::KEY_VALUE]
            ) {
                $cacheEntry->expiry = $entry[self::KEY_EXPIRY];
                $cacheEntry->value = $entry[self::KEY_VALUE];
                $cacheEntry->tags = $entry[self::KEY_TAGS] ?? [];
            }
        }
        return $cacheEntry;
    }

    public function __debugInfo(): array
    {
        return [
            'defaultLifetime' => $this->defaultLifetime,
        ];
    }

}
