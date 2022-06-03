<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\CacheDriver, Traits\StringableObject
};
use Psr\Log\LoggerAwareTrait,
    Traversable;

abstract class BaseCacheDriver implements CacheDriver
{

    use LoggerAwareTrait,
        StringableObject;

    protected int $defaultLifeTime = 0;

    /** {@inheritdoc} */
    public function setDefaultLifetime(int $defaultLifetime): void
    {
        $this->defaultLifeTime = max(0, $defaultLifetime);
    }

    /** {@inheritdoc} */
    public function purge(): void
    {

    }

    /** {@inheritdoc} */
    public function deleteMultiple(iterable $keys): Traversable
    {

        foreach ($keys as $key) {
            yield $key => $this->delete($key);
        }
    }

    /** {@inheritdoc} */
    public function getMultiple(iterable $keys): Traversable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key);
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(iterable $values, ?int $expiry = 0): Traversable
    {

        foreach ($values as $key => $value) {
            yield $key => $this->set($key, $value, $expiry);
        }
    }

    /**
     * Get a 32 Chars hashed key
     *
     * @param string $key
     * @return string
     */
    final protected function getHashedKey(string $key): string
    {
        // classname added to prevent conflicts on similar drivers
        // MD5 as we need speed and some filesystems are limited in length
        return hash('MD5', static::class . $key);
    }

    /**
     * Convenience function to check if item is expired status against current time
     * @param int|null $expiry
     * @return bool
     */
    final protected function isExpired(?int $expiry = null): bool
    {
        $expiry = $expiry ?? 0;
        return microtime(true) > $expiry;
    }

    /**
     * Convenience function to convert expiry into TTL
     * A TTL/expiry of 0 never expires
     *
     *
     * @param int $expiry
     * @return int the ttl a negative ttl is already expired
     */
    final protected function expiryToLifetime(int $expiry): int
    {
        return
                $expiry !== 0 ?
                $expiry - time() :
                0;
    }

}