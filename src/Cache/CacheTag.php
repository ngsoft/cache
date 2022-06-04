<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

class CacheTag implements \Stringable
{

    public function __construct(
            public readonly string $tag
    )
    {

    }

    public function getCacheKeys(TaggedCacheDriver $driver): iterable
    {

        return $driver->getTagged($this->tag);
    }

    public function __toString(): string
    {
        return $this->tag;
    }

}
