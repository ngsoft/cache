<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheDriver, InvalidArgumentException, Pool, Utils\BaseDriver
};
use Psr\SimpleCache\CacheInterface,
    Traversable;

/**
 * Driver for using a PSR16 Cache
 */
class SimpleCacheDriver extends BaseDriver implements CacheDriver {

    /** @var CacheInterface */
    protected $cacheProvider;

    /**
     * @param CacheInterface $simpleCacheProvider
     * @throws InvalidArgumentException
     */
    public function __construct(CacheInterface $simpleCacheProvider) {

        if (
                $simpleCacheProvider instanceof Pool
        ) {
            // to prevent infinite loops
            throw new InvalidArgumentException(sprintf(
                                    'Cannot use %s as %s, too much recursion.',
                                    get_class($simpleCacheProvider),
                                    CacheInterface::class
            ));
        }
        $this->cacheProvider = $simpleCacheProvider;
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return [
            static::class => [
                CacheInterface::class => get_class($this->cacheProvider),
            ]
        ];
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    protected function doClear(): bool {

        return $this->cacheProvider->clear();
    }

    /** {@inheritdoc} */
    protected function doContains(string $key): bool {
        return $this->cacheProvider->has($key);
    }

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        if (empty($keys)) return true;
        return $this->cacheProvider->deleteMultiple($keys);
    }

    /** {@inheritdoc} */
    protected function doFetch(string ...$keys): Traversable {
        if (empty($keys)) return;
        foreach ($this->cacheProvider->getMultiple($keys, null) as $key => $value) {
            yield $key => $value;
        }
    }

    /** {@inheritdoc} */
    protected function doSave(array $keysAndValues, int $expiry = 0): bool {
        if (empty($keysAndValues)) return true;
        $ttl = $this->expiryToLifetime($expiry);
        return $this->cacheProvider->setMultiple($keysAndValues, $ttl);
    }

    /**
     * Cannot know what method to call for that
     * {@inheritdoc}
     */
    public function purge(): bool {

        return false;
    }

}
