<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\CacheEntry;

class NullDriver extends BaseDriver
{

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        return $this->createCacheEntry($key, null);
    }

    public function has(string $key): bool
    {
        return false;
    }

}
