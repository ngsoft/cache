<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

final class CacheEntry
{

    public function __construct(
            public readonly string $key,
            public ?int $expiry = null,
            public mixed $value = null
    )
    {

    }

    public function isHit(): bool
    {
        if (null === $this->value) {
            return false;
        }
        if (is_int($this->expiry)) {
            return $this->expiry > microtime(true);
        }

        return true;
    }

}
