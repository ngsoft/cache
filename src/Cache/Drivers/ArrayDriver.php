<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\DataStructure\FixedArray;

class ArrayDriver extends BaseCacheDriver
{

    protected const DEFAULT_SIZE = 255;

    protected int $size;
    protected FixedArray $expiries;
    protected FixedArray $entries;

    public function __construct(
            int $size = self::DEFAULT_SIZE,
            int $defaultLifetime = 0
    )
    {
        if ($size === 0) $this->size = PHP_INT_MAX;
        else $this->size = max(1, $size);
        $this->defaultLifetime = max(0, $defaultLifetime);
        $this->clear();
    }

    public function purge(): void
    {

        foreach ($this->expiries as $hashedKey => $expiry) {
            if ($this->isExpired($expiry)) {
                unset($this->expiries[$hashedKey], $this->entries[$hashedKey]);
            }
        }
    }

    public function clear(): bool
    {

        $this->expiries = FixedArray::create($this->size);
        $this->entries = FixedArray::create($this->size);
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->expiries[$this->getHashedKey($key)], $this->entries[$this->getHashedKey($key)]);
    }

    public function get(string $key): mixed
    {

    }

    public function has(string $key): bool
    {

    }

    public function set(string $key, $value, int $expiry = 0): bool
    {

    }

    public function setTag(string $key, string|iterable $tags): bool
    {
        return false;
    }

    public function getTags(string $key): iterable
    {
        return [];
    }

    public function deleteTag(string|iterable $tag): bool
    {
        return false;
    }

}
