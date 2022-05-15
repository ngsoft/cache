<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use Psr\EventDispatcher\StoppableEventInterface;

class StoppableEvent implements StoppableEventInterface {

    /** @var bool */
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     *
     * @return static
     */
    public function stopPropagation(): static {
        $this->propagationStopped = true;
        return $this;
    }

}
