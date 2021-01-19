<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Doctrine\Common\Cache\{
    Cache, CacheProvider, ClearableCache, FlushableCache, MultiOperationCache
};
use NGSOFT\{
    Cache\CacheDriver, Cache\CacheUtils, Traits\Unserializable
};
use Traversable;

/**
 * To use Doctrine Cache Drivers
 *
 */
class DoctrineDriver implements CacheDriver {

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
        $this->doctrineProvider = $doctrineProvider;
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function setNamespace(string $namespace): void {
        if ($this->doctrineProvider instanceof CacheProvider) {
            $this->doctrineProvider->setNamespace($namespace);
        }
    }

    /** {@inheritdoc} */
    public function getNamespace(): string {
        return
                $this->doctrineProvider instanceof CacheProvider ?
                $this->doctrineProvider->getNamespace() :
                '';
    }

    /** {@inheritdoc} */
    public function clear(): bool {
        return
                $this->doctrineProvider instanceof FlushableCache ?
                $this->doctrineProvider->flushAll() :
                false;
    }

    /** {@inheritdoc} */
    public function invalidateAll(): bool {
        return $this->doctrineProvider instanceof ClearableCache ?
                $this->doctrineProvider->deleteAll() :
                false;
    }

    /**
     * Not implemented in Doctrine Cache (They invalidate items using deleteAll()(that delete nothing, just change the issued key.), or flushAll() to remove all datas)
     *
     * @return bool
     */
    public function purge(): bool {
        return false;
    }

    /** {@inheritdoc} */
    public function contains(string $key): bool {
        return $this->doctrineProvider->contains($key);
    }

    /** {@inheritdoc} */
    public function delete(string ...$keys): bool {
        if ($this->doctrineProvider instanceof MultiOperationCache) {
            return $this->doctrineProvider->deleteMultiple($keys);
        }
        // polyfill
        $r = true;
        foreach ($keys as $key) $r = $this->doctrineProvider->delete($key) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function fetch(string ...$keys): Traversable {
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
            $result = $this->doctrineProvider->fetch($key);
            yield $key => $result === false ? null : $result;
        }
    }

    /** {@inheritdoc} */
    public function save(array $keysAndValues, int $expiry = 0): bool {
        if (empty($keysAndValues)) return true;
        $lifeTime = $this->expiryToLifetime($expiry);
        if ($this->doctrineProvider instanceof MultiOperationCache) {
            return $this->doctrineProvider->saveMultiple($keysAndValues, $lifeTime);
        }
        // polyfill
        $r = true;
        foreach ($keysAndValues as $key => $value) {
            $r = $this->doctrineProvider->save($key, $value, $lifeTime) && $r;
        }
        return $r;
    }

}
