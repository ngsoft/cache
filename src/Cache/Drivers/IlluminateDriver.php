<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Illuminate\Contracts\Cache\Store,
    NGSOFT\Cache\CacheEntry;

class IlluminateDriver extends BaseDriver
{

    public function __construct(
            protected Store $provider
    )
    {

    }

    protected function doSet(string $key, mixed $value, int $expiry, array $tags): bool
    {
        return $this->provider->put($key, $this->createEntry($value, $expiry, $tags), $this->expiryToLifetime($expiry));
    }

    public function clear(): bool
    {
        return $this->provider->flush();
    }

    public function delete(string $key): bool
    {
        // some drivers returns false if entry does not exists in the first place
        $this->provider->forget($key);
        return true;
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        return $this->createCacheEntry($key, $this->provider->get($key));
    }

    public function has(string $key): bool
    {
        return $this->provider->get($key) !== null;
    }

}
