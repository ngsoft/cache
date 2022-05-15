<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use InvalidArgumentException,
    LogicException;
use Psr\EventDispatcher\{
    EventDispatcherInterface, StoppableEventInterface
};

trait EventDispatcherAware {

    private ?EventDispatcherInterface $eventDispatcher;

    /**
     * Get the proxied EventDispatcher
     *
     * @return ?EventDispatcherInterface
     */
    public function getEventDispatcher(): ?EventDispatcherInterface {
        return $this->eventDispatcher;
    }

    /**
     * Set an event dispatcher to forwards calls to
     *
     * @phan-suppress PhanTypeMismatchReturn
     * @param EventDispatcherInterface $eventDispatcher
     * @return static
     * @throws InvalidArgumentException
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): static {
        if ($eventDispatcher instanceof self) throw new LogicException(sprintf('Cannot forward events to %s.', static::class));
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Convenience method to forward event to registered dispatcher
     *
     * @param object      $event     The event to pass to the event handlers/listeners
     *
     * @return object The passed $event MUST be returned
     */
    public function dispatch(object $event): object {
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) return $event;
        return $this->eventDispatcher ? $this->eventDispatcher->dispatch($event) : $event;
    }

}
