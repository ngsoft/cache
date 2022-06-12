<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache, Cache\CacheEntry, Cache\Exceptions\InvalidArgument
};
use Psr\SimpleCache\CacheInterface;

class PSR16Driver extends BaseDriver
{

    protected int $defaultLifetime = self::LIFETIME_5YEARS;

    public function __construct(
            protected CacheInterface $provider
    )
    {
        if ($provider instanceof Cache) {
            throw new InvalidArgument(sprintf('Cannot use %s adapter.', $provider::class));
        }
    }

    public function clear(): bool
    {
        return $this->provider->clear();
    }

    public function delete(string $key): bool
    {
        return $this->provider->delete($key);
    }

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {
        return $this->provider->set($key, $this->createEntry($value, $this->lifetimeToExpiry($ttl), $tags), $ttl);
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        return $this->createCacheEntry($key, $this->provider->get($key));
    }

    public function has(string $key): bool
    {
        return $this->provider->has($key);
    }

    public function __debugInfo(): array
    {
        return [
            'defaultLifetime' => $this->defaultLifetime,
            'provider' => get_class($this->provider),
        ];
    }

}
