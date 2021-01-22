<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\Driver, Cache\InvalidArgumentException, Cache\SimpleCachePool, Cache\Utils\CacheUtils, Traits\Unserializable
};
use Psr\SimpleCache\CacheInterface,
    Traversable;

/**
 * Driver for using a PSR16 Cache
 */
final class SimpleCacheDriver implements Driver {

    use CacheUtils;
    use Unserializable;

    /** @var CacheInterface */
    protected $cacheProvider;

    /**
     * @param CacheInterface $simpleCacheProvider
     * @throws InvalidArgumentException
     */
    public function __construct(CacheInterface $simpleCacheProvider) {

        if (
                $simpleCacheProvider instanceof SimpleCachePool
        ) {
            // to prevent infinite loops
            throw new InvalidArgumentException(sprintf(
                                    'Cannot use %s as %s, too much recursion.',
                                    get_class($simpleCacheProvider),
                                    CacheInterface::class
            ));
        }
        $this->cacheProvider = $simpleCacheProvider;
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function purge(): bool {
        return false;
    }

    /** {@inheritdoc} */
    public function clear(): bool {
        return $this->cacheProvider->clear();
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool {
        return $this->cacheProvider->delete($key);
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys): bool {
        if (empty($keys)) return true;
        return $this->cacheProvider->deleteMultiple($keys);
    }

    /** {@inheritdoc} */
    public function get(string $key) {
        return $this->cacheProvider->get($key);
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys): Traversable {
        if (empty($keys)) return;
        foreach ($this->cacheProvider->getMultiple($keys) as $key => $value) {
            yield $key => $value;
        }
    }

    /** {@inheritdoc} */
    public function has(string $key): bool {
        return $this->cacheProvider->has($key);
    }

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {
        if ($this->isExpired($expiry)) return $this->delete($key);
        $ttl = $this->expiryToLifetime($expiry);
        if ($ttl === 0) $ttl = null;
        return $this->cacheProvider->set($key, $value, $ttl);
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, int $expiry = 0): bool {
        if (empty($values)) return true;
        if ($this->isExpired($expiry)) return $this->deleteMultiple(array_keys($values));
        $ttl = $this->expiryToLifetime($expiry);
        if ($ttl === 0) $ttl = null;
        return $this->cacheProvider->setMultiple($values, $ttl);
    }

}