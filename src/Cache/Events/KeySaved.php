<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Events;

class KeySaved extends CacheEvent
{

    public function __construct(string $key, public mixed $value)
    {
        parent::__construct($key);
    }

}
