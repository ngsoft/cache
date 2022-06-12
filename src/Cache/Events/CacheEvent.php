<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

use Psr\{
    Cache\CacheItemPoolInterface, EventDispatcher\StoppableEventInterface
};

class CacheEvent implements StoppableEventInterface
{

    protected bool $propagationStopped = false;

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

    /** {@inheritdoc} */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stop propagation for event
     *
     * @return void
     */
    public function stopPropagation(): void
    {

        $this->propagationStopped = true;
    }

}
