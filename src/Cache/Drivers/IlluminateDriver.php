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

/**
 * A Driver that makes PSR Caching compatible with Laravel Caching
 * It don't use the Facade, instead it uses illuminate/cache directly
 *
 * @link https://packagist.org/packages/illuminate/cache
 */
class IlluminateDriver extends BaseDriver implements CacheDriver {

    /** @var Store */
    protected $cacheStore;

    /**
     * @param Store $cacheStore
     * @throws InvalidArgumentException
     */
    public function __construct(Store $cacheStore) {
        $this->cacheStore = $cacheStore;
    }

}
