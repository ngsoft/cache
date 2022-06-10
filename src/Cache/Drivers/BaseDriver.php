<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException,
    NGSOFT\Cache\Interfaces\CacheDriver,
    Psr\Log\LoggerAwareTrait,
    Traversable;

class BaseDriver implements CacheDriver
{

    use LoggerAwareTrait;

    protected int $defaultLifetime = 0;

    public function getIterator(): Traversable
    {
        yield 0 => $this;
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
        return $default;
    }

    /** {@inheritdoc} */
    public function deleteMany(iterable $keys): iterable
    {

        foreach ($keys as $key) {
            yield $key => $this->delete($key);
        }
    }

    /** {@inheritdoc} */
    public function getMany(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /** {@inheritdoc} */
    public function setMany(iterable $values, ?int $ttl = null): iterable
    {

        foreach ($values as $key => $value) {

            yield $key => $this->set($key, $value);
        }
    }

    public function tag(string $key, string|array $tags): bool
    {

    }

    /** {@inheritdoc} */
    public function clearTags(string $key): bool
    {
        return $this->delete(sprintf(self::TAGGED_KEY_PREFIX, $key));
    }

    /** {@inheritdoc} */
    public function getTags(string $key): array
    {
        return $this->get(sprintf(static::TAGGED_KEY_PREFIX, $key), []);
    }

    public function invalidateTag(string|iterable $tags): bool
    {
        $result = true;

        $removed = [];

        foreach ($this->getTagged($tags) as $tag => $key) {
            if (isset($removed[$key])) {
                continue;
            }

            if ($this->delete($key)) {
                $removed[$key] = $key;
            } else $result = false;
        }
        return $result;
    }

    public function getTagged(string|iterable $tags): iterable
    {
        if (!is_iterable($tags)) {
            $tags = [$tags];
        }
        foreach ($tags as $tagName) {
            $tagKey = sprintf(static::TAG_PREFIX, $tagName);
            foreach ($this->get($tagKey, []) as $key) {
                yield $tagName => $key;
            }
        }
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
            return $this->defaultLifetime === 0 ? 0 : time() + $this->defaultLifetime;
        }

        return time() + $ttl;
    }

    /**
     * Convenience function to check if item is expired status against current time
     * @param int|null $expiry
     * @return bool
     */
    protected function isExpired(int $expiry = null): bool
    {
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
            return is_object($value) || is_array($value) ? \serialize($value) : $values;
        } catch (\Throwable) { return null; } finally { \restore_error_handler(); }
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
        } catch (\Throwable) { return null; } finally { \restore_error_handler(); }
    }

}
