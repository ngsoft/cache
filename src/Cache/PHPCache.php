<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\Cache\Drivers\{
    APCuDriver, ArrayDriver, ChainDriver, OPHPDriver
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

        $drivers = [new ArrayDriver()];
        if (APCuDriver::isSupported() and php_sapi_name() !== 'cli') $drivers[] = new APCuDriver();
        $drivers[] = new OPHPDriver($rootpath, $prefix);
        $chain = new ChainDriver($drivers);
        parent::__construct($chain, $defaultLifetime ?? 0);
    }

}
