<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Doctrine\Common\Cache\{
    Cache, ClearableCache, FlushableCache, MultiOperationCache
};
use NGSOFT\Cache as Cache2,
    RuntimeException;

if (!interface_exists(Cache::class)) {
    throw new RuntimeException('doctrine/cache not installed, please run: composer require doctrine/cache:^1.10.1');
}

class DoctrineProvider extends NamespaceAble implements Cache, FlushableCache, ClearableCache, MultiOperationCache, Cache2
{

    public function contains($id): bool
    {
        return $this->driver->has($this->getCacheKey($id));
    }

    public function delete($id): bool
    {
        return $this->driver->delete($this->getCacheKey($id));
    }

    public function fetch($id): mixed
    {

        return $this->driver->getRaw($this->getCacheKey($id));
    }

    public function getStats()
    {

    }

    public function save($id, $data, $lifeTime = 0): bool
    {
        return $this->driver->set($this->getCacheKey($id), $data, $this->lifeTimeToExpiry($lifeTime));
    }

    public function deleteAll(): bool
    {
        return $this->invalidateNamespace();
    }

    public function flushAll(): bool
    {
        $this->clearNamespace();
        return $this->driver->clear();
    }

    public function deleteMultiple(array $keys): bool
    {
        $result = true;
        foreach ($keys as $key) {
            $result = $this->delete($key) && $result;
        }

        return $result;
    }

    public function fetchMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->fetch($key);
        }
        return $result;
    }

    public function saveMultiple(array $keysAndValues, $lifetime = 0): bool
    {
        $result = true;

        foreach ($keysAndValues as $key => $value) {
            $result = $this->save($key, $value, $lifetime) && $result;
        }

        return $result;
    }

    protected function lifeTimeToExpiry(int $ttl): int
    {
        return $ttl === 0 ? $ttl : time() + $ttl;
    }

}
