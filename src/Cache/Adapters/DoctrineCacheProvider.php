<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Doctrine\Common\Cache\{
    Cache as DoctrineCache, ClearableCache, FlushableCache, MultiOperationCache
};
use NGSOFT\{
    Cache, Cache\Exceptions\CacheError
};

if (!interface_exists(DoctrineCache::class)) {
    throw new CacheError('doctrine/cache not installed, please run: composer require doctrine/cache:^1.10.1');
}

class DoctrineCacheProvider implements Cache, DoctrineCache, FlushableCache, ClearableCache, MultiOperationCache
{

}
