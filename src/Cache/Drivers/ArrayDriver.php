<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\CacheEntry, DataStructure\FixedArray
};

class ArrayDriver extends BaseDriver
{

    protected const DEFAULT_SIZE = 255;

    protected int $size;
    protected FixedArray $entries;

    public function __construct(
            int $size = self::DEFAULT_SIZE
    )
    {
        $this->size = $size === 0 ? PHP_INT_MAX : max(1, $size);
        $this->clear();
    }

    /** {@inheritdoc} */
    public function clear(): bool
    {
        $this->entries = new FixedArray($this->size);
        return true;
    }

    /** {@inheritdoc} */
    public function purge(): void
    {

        foreach ($this->entries as $key => $entry) {
            if ($this->isExpired($entry[self::KEY_EXPIRY])) {
                unset($this->entries[$key]);
            }
        }
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool
    {
        unset($this->entries[$key]);
        return true;
    }

    /** {@inheritdoc} */
    public function getCacheEntry(string $key): CacheEntry
    {
        $this->purge();
        $cacheEntry = $this->createCacheEntry($key, $this->entries[$key]);
        $cacheEntry->value = $cacheEntry->isHit() ? $this->unserializeEntry($cacheEntry->value) : null;
        return $cacheEntry;
    }

    /** {@inheritdoc} */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    protected function doSet(string $key, mixed $value, int $expiry, array $tags): bool
    {
        $this->entries[$key] = $this->createEntry($this->serializeEntry($value), $expiry, $tags);
        return true;
    }

}
