<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Psr\Cache\CacheItemPoolInterface;

class_exists(Item::class);

final class CachePool extends NamespaceAble implements CacheItemPoolInterface
{

    public function __construct(
            TaggedCacheDriver $driver,
            protected int $defaultLifetime = 0,
            string $namespace = ''
    )
    {
        parent::__construct($driver, $namespace);
    }

}
