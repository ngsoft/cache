<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache, Cache\CacheEntry, Cache\Exceptions\InvalidArgument
};
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};

class PSR6Driver extends BaseDriver
{

    /**
     * @var CacheItemInterface[]
     */
    protected array $items = [];
    protected int $defaultLifetime = self::LIFETIME_5YEARS;

    public function __construct(
            protected CacheItemPoolInterface $provider
    )
    {

        if ($provider instanceof Cache) {
            throw new InvalidArgument(sprintf('Cannot use %s adapter.', $provider::class));
        }
    }

    protected function getItem(string $key): CacheItemInterface
    {

        if (!isset($this->items[$key])) {
            $this->items[$key] = $this->provider->getItem($key);
        }

        return $this->items[$key];
    }

    protected function pullItem(string $key): CacheItemInterface
    {
        $item = $this->items[$key] ?? $this->provider->getItem($key);
        unset($this->items[$key]);
        return $item;
    }

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {
        return $this->provider->save(
                        $this
                                ->pullItem($key)
                                ->set($this->createEntry($value, $this->lifetimeToExpiry($ttl), $tags))
                                ->expiresAfter($this->getLifetime($ttl))
        );
    }

    public function getCacheEntry(string $key): CacheEntry
    {

        $item = $this->getItem($key);
        if ($item->isHit()) {
            return $this->createCacheEntry($key, $item->get());
        }
        return CacheEntry::createEmpty($key);
    }

    public function clear(): bool
    {
        return $this->provider->clear();
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);
        return $this->provider->deleteItem($key);
    }

    public function has(string $key): bool
    {
        return $this->provider->hasItem($key);
    }

    public function __debugInfo(): array
    {
        return [
            'defaultLifetime' => $this->defaultLifetime,
            'provider' => get_class($this->provider),
        ];
    }

}
