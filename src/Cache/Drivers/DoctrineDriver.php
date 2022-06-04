<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Doctrine\Common\Cache\CacheProvider,
    NGSOFT\Cache\CacheEntry;

class DoctrineDriver extends BaseCacheDriver
{

    public function __construct(
            protected CacheProvider $provider
    )
    {

    }

    public function clear(): bool
    {
        return $this->provider->flushAll();
    }

    public function delete(string $key): bool
    {
        return $this->provider->delete($key);
    }

    public function get(string $key): CacheEntry
    {
        $result = $this->provider->fetch($key);
        return $result instanceof CacheEntry ? $result : CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->provider->contains($key);
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {

        $expiry = $expiry === 0 ? PHP_INT_MAX : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($this->isExpired($expiry)) {
            return $this->delete($key);
        }
        return $this->provider->save($key, CacheEntry::create($key, $expiry, $value), $this->expiryToLifetime($expiry));
    }

}
