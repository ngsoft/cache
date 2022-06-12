<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

use Psr\Cache\CacheItemPoolInterface;

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
