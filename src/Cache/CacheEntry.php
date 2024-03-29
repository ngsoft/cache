<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Stringable;

final class CacheEntry implements Stringable
{

    public function __construct(
            public readonly string $key,
            public int $expiry = 0,
            public mixed $value = null,
            public array $tags = []
    )
    {

    }

    public function getCacheItem(string $key): CacheItem
    {

        if ($this->isHit()) {
            return CacheItem::create($key, [
                        CacheItem::METADATA_EXPIRY => $this->expiry === 0 ? null : $this->expiry,
                        CacheItem::METADATA_VALUE => $this->value,
                        CacheItem::METADATA_TAGS => $this->tags
            ]);
        }

        return CacheItem::create($key);
    }

    public function isHit(): bool
    {
        if (null === $this->value) {
            return false;
        }

        return $this->expiry === 0 || $this->expiry > microtime(true);
    }

    public static function create(string $key, int $expiry, mixed $value, array $tags): static
    {
        return new static($key, $expiry, $value, $tags);
    }

    public static function createEmpty(string $key): static
    {
        return new static($key);
    }

    public function __serialize(): array
    {
        return [$this->key, $this->expiry, $this->value, $this->tags];
    }

    public function __unserialize(array $data): void
    {
        list($this->key, $this->expiry, $this->value, $this->tags) = $data;
    }

    public function __toString(): string
    {
        return $this->key;
    }

}
