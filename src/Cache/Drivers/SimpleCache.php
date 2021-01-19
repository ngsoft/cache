<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    BaseDriver, CacheDriver, InvalidArgumentException, Pool
};
use Psr\SimpleCache\CacheInterface;

/**
 * Driver for using a PSR16 Cache
 */
class SimpleCache extends BaseDriver implements CacheDriver {

    /** @var CacheInterface */
    protected $cacheProvider;

    public function __construct(CacheInterface $cacheProvider) {

        if (
        $cacheProvider instanceof Pool or
        ($cacheProvider instanceof \NGSOFT\Cache\Utils\SimpleCachePool and )
        ) {
            // to prevent infinite loops
            throw new InvalidArgumentException(sprintf(
                                    'Cannot use %s as %s, too much recursion.',
                                    get_class($cacheProvider),
                                    CacheInterface::class
            ));
        }
    }

}
