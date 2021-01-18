<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\Cache\Drivers\{
    APCuDriver, ArrayCache, OPHPCache
};

/**
 * Caches data using php scripting
 * Not Taggable to increase performances
 */
class PHPCache extends CacheItemPool {

    /**
     * @param string|null $rootpath Root directory to store the cache (defaults to tmp)
     * @param int|null $defaultLifetime default TTL to use to store the files (defaults to 0 =>  never expires)
     * @param string $prefix prefix to use (defaults to phpcache)
     */
    public function __construct(
            string $rootpath = null,
            int $defaultLifetime = null,
            string $prefix = 'phpcache'
    ) {

        $drivers = [new ArrayCache()];
        if (APCuDriver::isSupported() and php_sapi_name() !== 'cli') $drivers[] = new APCuDriver();
        $drivers[] = new OPHPCache($rootpath, $prefix);
        $chain = new Drivers\ChainCache($drivers);
        parent::__construct($chain, $defaultLifetime ?? 0);
    }

}
