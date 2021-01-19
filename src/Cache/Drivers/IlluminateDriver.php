<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Illuminate\Contracts\Cache\Store;
use NGSOFT\Cache\{
    BaseDriver, CacheDriver, InvalidArgumentException
};
use Traversable;

/**
 * A Driver that makes PSR Caching compatible with Laravel Caching
 * It don't use the Facade, instead it uses illuminate/cache directly
 *
 * you can also use Illuminate\Cache\Repository with SimpleCacheDriver
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

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    protected function doClear(): bool {
        return $this->cacheStore->flush();
    }

    /** {@inheritdoc} */
    protected function doContains(string $key): bool {
        return $this->cacheStore->get($key) === null;
    }

    protected function doDelete(string ...$keys): bool {

    }

    protected function doFetch(string ...$keys): Traversable {

    }

    protected function doSave(array $keysAndValues, int $expiry = 0): bool {

    }

    /**
     * Not implemented
     * {@inheritdoc}
     */
    public function purge(): bool {
        return false;
    }

}
