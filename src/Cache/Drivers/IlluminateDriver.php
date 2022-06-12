<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Illuminate\Contracts\Cache\Store;
use NGSOFT\{
    Cache, Cache\CacheEntry, Cache\Exceptions\InvalidArgument
};

class IlluminateDriver extends BaseDriver
{

    public function __construct(
            protected Store $provider
    )
    {

        if ($provider instanceof Cache) {
            throw new InvalidArgument(sprintf('Cannot use %s adapter.', $provider::class));
        }
    }

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {
        return $this->provider->put($key, $this->createEntry($value, $this->lifetimeToExpiry($ttl), $tags), $this->getLifetime($ttl));
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

    public function __debugInfo(): array
    {
        return [
            'defaultLifetime' => $this->defaultLifetime,
            'provider' => get_class($this->provider),
        ];
    }

}
