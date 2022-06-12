<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

final class CacheEntry implements \Stringable
{

    public function __construct(
            public readonly string $key,
            public int $expiry = 0,
            public mixed $value = null,
            public array $tags = []
    )
    {

    }

    public function getCacheItem(): Item
    {

        if ($this->isHit()) {
            return Item::create($this->key, [
                        Item::METADATA_EXPIRY => $this->expiry === 0 ? null : $this->expiry,
                        Item::METADATA_VALUE => $this->value,
                        Item::METADATA_TAGS => $this->tags
            ]);
        }

        return Item::create($this->key);
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
