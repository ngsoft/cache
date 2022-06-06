<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use InvalidArgumentException;
use NGSOFT\Cache\{
    CacheEntry, CachePool
};
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};

class CachePoolDriver extends BaseCacheDriver
{

    /** @var CacheItemInterface[] */
    protected array $items = [];

    public function __construct(
            protected CacheItemPoolInterface $provider
    )
    {
        if ($provider instanceof CachePool) {
            throw new InvalidArgumentException(sprintf('Cannot use %s adapter.', CachePool::class));
        }
    }

    public function clear(): bool
    {
        $this->items = [];
        return $this->provider->clear();
    }

    public function delete(string $key): bool
    {
        unset($this->items[$this->getHashedKey($key)]);
        return $this->provider->deleteItem($key);
    }

    public function get(string $key): CacheEntry
    {
        $item = $this->items[$this->getHashedKey($key)] = $this->provider->getItem($key);

        $result = $item->isHit() ? $item->get() : null;
        return is_array($result) ? CacheEntry::create($key, $result['expiry'], $result['value']) : CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->provider->hasItem($key);
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {

        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($this->isExpired($expiry) || null === $value) {
            return $this->delete($key);
        }
        /** @var CacheItemInterface $item */
        $item = $this->items[$this->getHashedKey($key)] ?? $this->provider->getItem($key);
        unset($this->items[$this->getHashedKey($key)]);
        $ttl = $this->expiryToLifetime($expiry);

        try {
            return $this->provider->save(
                            $item
                                    ->set(['expiry' => $expiry, 'value' => $value])
                                    ->expiresAfter($ttl === 0 ? null : $ttl)
            );
        } catch (\Throwable) {
            return false;
        }
    }

}
