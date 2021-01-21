<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

/**
 * Dispatche on Cache Hit
 */
class CacheHit extends CacheEvent {

    /** @var mixed */
    protected $value;

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __construct(string $key, $value) {
        parent::__construct($key);
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return void
     */
    public function setValue($value): void {
        $this->value = $value;
    }

}
