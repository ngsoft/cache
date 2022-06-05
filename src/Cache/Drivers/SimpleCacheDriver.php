<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use InvalidArgumentException;
use NGSOFT\Cache\{
    CacheEntry, SimpleCachePool
};
use Psr\SimpleCache\CacheInterface;

class SimpleCacheDriver extends BaseCacheDriver
{

    public function __construct(
            protected CacheInterface $provider
    )
    {

        if ($provider instanceof SimpleCachePool) {
            throw new InvalidArgumentException(sprintf('Cannot use %s adapter.', SimpleCachePool::class));
        }
    }

    /** {@inheritdoc} */
    public function clear(): bool
    {

        return $this->provider->clear();
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool
    {

        return $this->provider->delete($key);
    }

    /** {@inheritdoc} */
    public function get(string $key): CacheEntry
    {
        $result = $this->provider->get($key);
        return is_array($result) ? CacheEntry::create($key, $result['expiry'], $result['value']) : CacheEntry::createEmpty($key);
    }

    /** {@inheritdoc} */
    public function has(string $key): bool
    {
        return $this->provider->has($key);
    }

    /** {@inheritdoc} */
    public function set(string $key, mixed $value, int $expiry = 0): bool
    {
        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($this->isExpired($expiry)) {
            return $this->delete($key);
        }
        $ttl = $this->expiryToLifetime($expiry);
        return $this->provider->set($key, ['expiry' => $expiry, 'value' => $value], $ttl === 0 ? null : $ttl);
    }

}
