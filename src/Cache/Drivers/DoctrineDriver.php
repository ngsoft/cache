<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Doctrine\Common\Cache\CacheProvider;
use NGSOFT\Cache\{
    CacheEntry, Exceptions\CacheError
};

if (!interface_exists(CacheProvider::class)) {
    throw new CacheError('doctrine/cache not installed, please run: composer require doctrine/cache:^1.10.1');
}

class DoctrineDriver extends BaseDriver
{

    public function __construct(
            protected CacheProvider $provider
    )
    {

    }

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {
        return $this->provider->save($key, $this->createEntry($value, $this->lifetimeToExpiry($ttl), $tags), $this->getLifetime($ttl));
    }

    public function clear(): bool
    {
        return $this->provider->flushAll();
    }

    public function delete(string $key): bool
    {
        return $this->provider->delete($key);
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        return $this->createCacheEntry($key, $this->provider->fetch($key));
    }

    public function has(string $key): bool
    {
        return $this->provider->contains($key);
    }

    public function __debugInfo(): array
    {
        return [
            'defaultLifetime' => $this->defaultLifetime,
            'provider' => get_class($this->provider),
        ];
    }

}
