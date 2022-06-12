<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

class CacheHit extends CacheEvent
{

    public function __construct(
            CacheItemPoolInterface $cachePool,
            string $key,
            public mixed $value
    )
    {

        parent::__construct($cachePool, $key);
    }

}
