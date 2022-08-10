<?php

declare(strict_types=1);

namespace NGSOFT\Facades;

use NGSOFT\{
    Cache\PHPCache, Container\ContainerInterface, Container\ServiceProvider, Container\SimpleServiceProvider
};
use Psr\Cache\CacheItemPoolInterface;

class Cache extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'CacheFacade';
    }

    protected static function getServiceProvider(): ServiceProvider
    {
        $provides = [PHPCache::class, CacheItemPoolInterface::class];

        return new SimpleServiceProvider(
                $provides,
                function (ContainerInterface $container) use ($provides) {
                    $cache = new PHPCache();

                    foreach ($provides as $id) {
                        if ( ! $container->has($id)) {
                            $container->set($id, $cache);
                        }
                    }
                }
        );
    }

}
