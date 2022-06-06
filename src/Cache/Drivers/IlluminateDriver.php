<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Illuminate\Contracts\Cache\Store,
    NGSOFT\Cache\CacheEntry;

class IlluminateDriver extends BaseCacheDriver
{

    public function __construct(
            protected Store $provider
    )
    {

    }

    public function clear(): bool
    {
        return $this->provider->flush();
    }

    public function delete(string $key): bool
    {
        $this->provider->forget($key);
        return !$this->has($key);
    }

    public function get(string $key): CacheEntry
    {
        $result = $this->provider->get($key);
        return is_array($result) ? CacheEntry::create($key, $result['expiry'], $result['value']) : CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->provider->get($key) === null;
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {
        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($this->isExpired($expiry) || null === $value) {
            return $this->delete($key);
        }

        $entry = ['expiry' => $expiry, 'value' => $value];

        return $expiry === 0 ?
                $this->provider->forever($key, $entry) :
                $this->provider->put($key, $entry, $this->expiryToLifetime($expiry));
    }

}
