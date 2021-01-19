<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Illuminate\Contracts\Cache\Store;
use NGSOFT\Cache\{
    BaseDriver, CacheDriver, InvalidArgumentException
};
use Traversable;

/**
 * A Driver that makes PSR-6 Caching compatible with Laravel Caching
 * It don't use the Facade, instead it uses illuminate/cache directly
 *
 * you can also use Illuminate\Cache\Repository with SimpleCacheDriver(but that is too many abstraction layers)
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
        return $this->cacheStore->get($key) !== null;
    }

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        if (empty($keys)) return true;
        $r = true;
        foreach ($keys as $key) {
            // some stores returns false if the data do not exists in the first place (ArrayStore ...) so:
            $this->cacheStore->forget($key);
            $r = !$this->doContains($key) && $r;
            // must do the trick
        }
        return $r;
    }

    /** {@inheritdoc} */
    protected function doFetch(string ...$keys): Traversable {
        if (empty($keys)) return;
        foreach ($this->cacheStore->many($keys) as $key => $value) {
            // already implements null on fail
            yield $key => $value;
        }
    }

    /** {@inheritdoc} */
    protected function doSave(array $keysAndValues, int $expiry = 0): bool {
        $ttl = $this->expiryToLifetime($expiry);
        // some drivers supports putMany with $ttl=0 =~ forever, some don't
        if ($ttl > 0) return $this->cacheStore->putMany($keysAndValues, $ttl);
        $r = true;
        foreach ($keysAndValues as $key => $value) {
            $r = $this->cacheStore->forever($key, $value) && $r;
        }
        return $r;
    }

    /**
     * Not implemented
     * {@inheritdoc}
     */
    public function purge(): bool {
        return false;
    }

}
