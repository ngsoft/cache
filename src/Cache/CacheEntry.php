<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

final class CacheEntry
{

    public function __construct(
            public readonly string $key,
            public int $expiry = 0,
            public mixed $value = null
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

}
