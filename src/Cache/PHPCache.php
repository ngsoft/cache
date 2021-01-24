<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\Cache\Drivers\{
    APCuDriver, ArrayDriver, ChainDriver, OPCacheDriver
};

/**
 * A preconfigured cache pool
 */
final class PHPCache extends CacheItemPool {

    /**
     * @param string|null $rootpath Root directory to store the cache (defaults to tmp)
     * @param int|null $defaultLifetime default TTL to use to store the files (defaults to 0 =>  never expires)
     * @param string $prefix prefix to use for rootpath (defaults to phpcache)
     */
    public function __construct(
            string $rootpath = null,
            int $defaultLifetime = null,
            string $prefix = 'phpcache'
    ) {

        $drivers = [new ArrayDriver()]; //useful to cache the already fetched data
        if (APCuDriver::isSupported() and php_sapi_name() !== 'cli') $drivers[] = new APCuDriver(); // disabled in cli mode as it works as an array cache
        $drivers[] = new OPCacheDriver($rootpath, $prefix); // in 2nd/third position as it is the less faster (it serves to warm APCu, as it can flush data before expired)
        $chain = new ChainDriver($drivers);
        parent::__construct($chain, $defaultLifetime ?? 0);
    }

}
