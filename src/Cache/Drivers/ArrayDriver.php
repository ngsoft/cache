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

        unset($this->entries[$this->getHashedKey($key)]);
        return true;
    }

    /** {@inheritdoc} */
    public function getCacheEntry(string $key): CacheEntry
    {
        $cacheEntry = CacheEntry::createEmpty($key);

        if ($entry = $this->entries[$this->getHashedKey($key)]) {

            $unserialized = $this->unserializeEntry($entry[self::KEY_VALUE]);
            if ($unserialized !== null) {
                $cacheEntry->expiry = $entry[self::KEY_EXPIRY];
                $cacheEntry->value = $unserialized;
                $cacheEntry->tags = $this->getTags($key);
            } else $this->delete($key);
        }
        return $cacheEntry;
    }

    /** {@inheritdoc} */
    public function has(string $key): bool
    {
        $this->purge();
        return $this->get($key) !== null;
    }

    /** {@inheritdoc} */
    public function set(string $key, mixed $value, mixed $ttl = null): bool
    {


        $this->clearTags($key);
        $expiry = $this->lifetimeToExpiry($ttl);
        $serialized = $this->serializeEntry($value);
        var_dump(__METHOD__, func_get_args(), $expiry, $serialized);

        if ($this->isExpired($expiry) || $serialized === null) {
            $this->delete($key);
            return $serialized !== null;
        }


        $this->entries[$this->getHashedKey($key)] = [
            self::KEY_EXPIRY => $expiry,
            self::KEY_VALUE => $serialized,
        ];

        return true;
    }

}
