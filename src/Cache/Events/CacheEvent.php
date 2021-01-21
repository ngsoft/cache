<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

use NGSOFT\Events\StoppableEvent;

/**
 * Base Event For Cache Pool
 */
abstract class CacheEvent extends StoppableEvent {

    /** @var string */
    protected $key;

    public function __construct(string $key) {
        $this->key = $key;
    }

    /**
     * The Cache Key
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

}
