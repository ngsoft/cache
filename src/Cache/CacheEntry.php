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

    public function isHit(): bool
    {
        if (null === $this->value) {
            return false;
        }

        return $this->expiry === 0 || $this->expiry > microtime(true);
    }

    public static function create(string $key, mixed $value, int $expiry, array $tags): static
    {
        return new static($key, $expiry, $value);
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
