<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

use NGSOFT\Traits\StoppableEventTrait;
use Psr\{
    Cache\CacheItemPoolInterface, EventDispatcher\StoppableEventInterface
};

class CacheEvent implements StoppableEventInterface
{

    use StoppableEventTrait;

    public function __construct(
            protected CacheItemPoolInterface $cachePool,
            public readonly string $key
    )
    {

    }

    public function getCachePool(): CacheItemPoolInterface
    {
        return $this->cachePool;
    }

}
