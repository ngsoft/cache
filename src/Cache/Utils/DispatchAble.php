<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\Events\CacheEvent,
    Psr\EventDispatcher\EventDispatcherInterface;

class DispatchAble
{

    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatchEvent(CacheEvent $event): CacheEvent
    {
        return $this->eventDispatcher?->dispatch($event) ?? $event;
    }

}
