<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

use Psr\EventDispatcher\StoppableEventInterface;

class CacheEvent implements StoppableEventInterface
{

    protected bool $propagationStopped = false;

    public function __construct(
            public readonly string $key
    )
    {

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
