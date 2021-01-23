<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Illuminate\Contracts\Cache\Store;
use NGSOFT\Cache\{
    Driver, InvalidArgumentException, Utils\BaseDriver
};

/**
 * A Driver that makes PSR-6 Caching compatible with Laravel Caching
 * It don't use the Facade, instead it uses illuminate/cache directly
 *
 * you can also use Illuminate\Cache\Repository with SimpleCacheDriver(but that is too many abstraction layers)
 * @link https://packagist.org/packages/illuminate/cache
 */
final class IlluminateDriver extends BaseDriver implements Driver {

    /** @var Store */
    protected $cacheStore;

    /**
     * @param Store $cacheStore
     * @throws InvalidArgumentException
     */
    public function __construct(Store $cacheStore) {
        $this->cacheStore = $cacheStore;
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return [
            static::class => [
                Store::class => get_class($this->cacheStore)
            ]
        ];
    }

    ////////////////////////////   API   ////////////////////////////
    // Illuminate trait RetrievesMultipleKeys = ~ BaseDriver multiples

    /** {@inheritdoc} */
    public function clear(): bool {
        return $this->cacheStore->flush();
    }

    /** {@inheritdoc} */
    public function has(string $key): bool {
        return $this->cacheStore->get($key) !== null;
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool {
        // some stores returns false if the data do not exists in the first place (ArrayStore ...) so:
        $this->cacheStore->forget($key);
        return !$this->has($key);
    }

    /** {@inheritdoc} */
    public function get(string $key) {
        return $this->cacheStore->get($key);
    }

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {
        $ttl = $this->expiryToLifetime($expiry);
        if ($ttl == 0) return $this->cacheStore->forever($key, $value);
        return $this->cacheStore->put($key, $value, $ttl);
    }

}
