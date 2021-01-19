<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    BaseDriver, CacheDriver, InvalidArgumentException
};
use Pool;
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};

class IlluminateDriver extends BaseDriver implements CacheDriver {

    /** @var CacheItemPoolInterface */
    protected $cacheProvider;

    /**
     * Keep track of the issued items
     * Preventing loading them multiple times
     *
     * @var CacheItemInterface[]
     */
    protected $issued = [];

    /**
     * @param CacheItemPoolInterface $cacheProvider
     * @throws InvalidArgumentException
     */
    public function __construct(CacheItemPoolInterface $cacheProvider) {

        if (
                $cacheProvider instanceof Pool
        ) {
            // to prevent infinite loops
            throw new InvalidArgumentException(sprintf(
                                    'Cannot use %s as %s, too much recursion.',
                                    get_class($cacheProvider),
                                    CacheItemPoolInterface::class
            ));
        }
        $this->cacheProvider = $cacheProvider;
    }

}
