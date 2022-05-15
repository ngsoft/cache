<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Doctrine\Common\Cache\{
    Cache, CacheProvider, ClearableCache, FlushableCache, MultiOperationCache
};
use NGSOFT\{
    Cache\DoctrineCache, Cache\Driver, Cache\InvalidArgumentException, Cache\Utils\CacheUtils, Traits\Unserializable
};
use Traversable;

/**
 * To use Doctrine Cache Providers
 */
final class DoctrineDriver implements Driver {

    use CacheUtils;
    use Unserializable;

    /**
     * I do not use the abstract provider as a provider
     * as 3rd party cache can implements just the Cache interface
     *
     * @var Cache|MultiOperationCache|FlushableCache|ClearableCache|CacheProvider
     */
    protected $doctrineProvider;

    /**
     * @param Cache $doctrineProvider a doctrine cache instance
     */
    public function __construct(
            Cache $doctrineProvider
    ) {
        if (
                $doctrineProvider instanceof DoctrineCache
        ) {
            // to prevent infinite loops
            throw new InvalidArgumentException(sprintf(
                                    'Cannot use %s as %s, too much recursion.',
                                    get_class($doctrineProvider),
                                    Cache::class
            ));
        }
        $this->doctrineProvider = $doctrineProvider;
    }

    /** {@inheritdoc} */
    public function jsonSerialize(): mixed {
        return [
            static::class => [
                Cache::class => get_class($this->doctrineProvider),
                "Stats" => $this->doctrineProvider->getStats()
            ]
        ];
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function setDefaultLifetime(int $defaultLifetime): void {

    }

    /** {@inheritdoc} */
    public function purge(): bool {
        return false;
    }

    /** {@inheritdoc} */
    public function clear(): bool {
        return
                $this->doctrineProvider instanceof FlushableCache ?
                $this->doctrineProvider->flushAll() :
                false;
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool {
        return $this->doctrineProvider->delete($key);
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys): bool {
        if (empty($keys)) return true;
        $keys = array_values(array_unique($keys));
        if ($this->doctrineProvider instanceof MultiOperationCache) {
            return $this->doctrineProvider->deleteMultiple($keys);
        }
        $r = true;
        foreach ($keys as $key) $r = $this->delete($key) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function get(string $key) {
        $result = $this->doctrineProvider->fetch($key);
        return $result === false ? null : $result;
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys): Traversable {
        if (empty($keys)) return;
        if ($this->doctrineProvider instanceof MultiOperationCache) {
            $fetched = $this->doctrineProvider->fetchMultiple($keys);
            foreach ($keys as $key) {
                if (array_key_exists($key, $fetched)) yield $key => $fetched[$key];
                else yield $key => null;
            }
            return;
        }
        // polyfill
        foreach ($keys as $key) {
            yield $key => $this->get($key);
        }
    }

    /** {@inheritdoc} */
    public function has(string $key): bool {
        return $this->doctrineProvider->contains($key);
    }

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {
        if ($this->isExpired($expiry)) return $this->delete($key);
        $lifeTime = $this->expiryToLifetime($expiry);
        return $this->doctrineProvider->save($key, $value, $lifeTime);
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, int $expiry = 0): bool {
        if (empty($values)) return true;
        if ($this->isExpired($expiry)) return $this->deleteMultiple(array_keys($values));
        $lifeTime = $this->expiryToLifetime($expiry);
        if ($this->doctrineProvider instanceof MultiOperationCache) {
            return $this->doctrineProvider->saveMultiple($values, $lifeTime);
        }
        // polyfill
        $r = true;
        foreach ($values as $key => $value) {
            $r = $this->doctrineProvider->save($key, $value, $lifeTime) && $r;
        }
        return $r;
    }

}
