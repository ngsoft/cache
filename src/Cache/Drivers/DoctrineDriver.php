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
        return is_array($result) ? CacheEntry::create($key, $result['expiry'], $result['value']) : CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->provider->contains($key);
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {

        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($this->isExpired($expiry)) {
            return $this->delete($key);
        }

        return $this->provider->save($key, ['expiry' => $expiry, 'value' => $value], $this->expiryToLifetime($expiry));
    }

}
