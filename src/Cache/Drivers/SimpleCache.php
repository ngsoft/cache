<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    BaseDriver, CacheDriver, InvalidArgumentException, Pool
};
use Psr\SimpleCache\CacheInterface;

/**
 * Driver for using a PSR16 Cache
 * Or proxying PSR6 Cache using SimpleCachePool
 */
class SimpleCache extends BaseDriver implements CacheDriver {

    /** @var CacheInterface */
    protected $cacheProvider;

    /**
     * @param CacheInterface $simpleCacheProvider
     * @throws InvalidArgumentException
     */
    public function __construct(CacheInterface $simpleCacheProvider) {

        if (
                $simpleCacheProvider instanceof Pool
        ) {
            // to prevent infinite loops
            throw new InvalidArgumentException(sprintf(
                                    'Cannot use %s as %s, too much recursion.',
                                    get_class($cacheProvider),
                                    CacheInterface::class
            ));
        }
        $this->cacheProvider = $simpleCacheProvider;
    }

}
