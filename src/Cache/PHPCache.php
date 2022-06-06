<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\Cache\Drivers\{
    ApcuDriver, ArrayDriver, ChainDriver, PhpDriver
};

/**
 * A preconfigured cache pool without tags
 * Chains ArrayDriver, ApcuDriver, PhpDriver
 */
class PHPCache extends CachePool
{

    /**
     * @param string $rootpath PhpDriver root directory
     * @param string $prefix PhpDriver prefix
     * @param int $defaultLifetime null expiry value
     * @param string $namespace Cache namespace
     */
    public function __construct(
            string $rootpath = '',
            string $prefix = '',
            int $defaultLifetime = 0,
            string $namespace = '',
    )
    {

        $drivers = [
            new ArrayDriver()
        ];

        if (ApcuDriver::isSupported()) {
            //  $drivers[] = new ApcuDriver();
        }
        $drivers [] = new PhpDriver($rootpath, $prefix);
        parent::__construct(new ChainDriver($drivers), $defaultLifetime, $namespace);
    }

    /**
     * Append driver to the ChainDriver
     * @param TaggedCacheDriver $driver
     * @return void
     */
    public function appendDriver(TaggedCacheDriver $driver): void
    {
        $drivers = [];

        if ($this->driver instanceof \Traversable) {
            foreach ($this->driver as $provider) {

                if ($provider === $driver) {
                    throw new CacheError('Cannot chain the same driver twice.');
                }
                $drivers[] = $provider;
            }
        } else $drivers[] = $this->driver;


        $drivers[] = $driver;

        $this->driver = new ChainDriver($drivers);
        $this->driver->setDefaultLifetime($this->defaultLifetime);
    }

    /**
     * Prepend Driver to the ChainDriver
     *
     * @param TaggedCacheDriver $driver
     * @return void
     */
    public function prependDriver(TaggedCacheDriver $driver): void
    {

        $drivers = [$driver];

        if ($this->driver instanceof \Traversable) {
            foreach ($this->driver as $provider) {

                if ($provider === $driver) {
                    throw new CacheError('Cannot chain the same driver twice.');
                }
                $drivers[] = $provider;
            }
        } else $drivers[] = $this->driver;

        $this->driver = new ChainDriver($drivers);
        $this->driver->setDefaultLifetime($this->defaultLifetime);
    }

}
