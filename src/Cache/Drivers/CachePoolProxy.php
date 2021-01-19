<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    BaseDriver, CacheDriver, InvalidArgumentException, Pool
};
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};
use Traversable;

/**
 * Use another Cache Pool as Driver (ChainCache or Namespace Support, I don't know why else it would be used...)
 * If provided cache pool has others features(tags for example), they will not be used
 */
class CachePoolProxy extends BaseDriver implements CacheDriver {

    /** @var CacheItemPoolInterface */
    protected $cacheProvider;

    /**
     * Keep track of the issued items
     * Preventing loading them multiple times
     *
     * @var CacheItemInterface[]
     */
    protected $issued = [];

    /**
     * @param CacheItemPoolInterface $cacheProvider
     * @throws InvalidArgumentException
     */
    public function __construct(CacheItemPoolInterface $cacheProvider) {

        if (
                $cacheProvider instanceof Pool
        ) {
            // to prevent infinite loops
            throw new InvalidArgumentException(sprintf(
                                    'Cannot use %s as %s, too much recursion.',
                                    get_class($cacheProvider),
                                    CacheItemPoolInterface::class
            ));
        }
        $this->cacheProvider = $cacheProvider;
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    protected function doClear(): bool {
        return $this->cacheProvider->clear();
    }

    /** {@inheritdoc} */
    protected function doContains(string $key): bool {
        return $this->cacheProvider->hasItem($key);
    }

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        if (empty($keys)) return true;
        foreach ($keys as $key) unset($this->issued[$this->getHashedKey($key)]);
        return $this->cacheProvider->deleteItems($keys);
    }

    /** {@inheritdoc} */
    protected function doFetch(string ...$keys): Traversable {
        if (empty($keys)) return;
        /** @var CacheItemInterface $item */
        foreach ($this->cacheProvider->getItems($keys) as $key => $item) {
            // keep the item to save it later
            $this->issued[$this->getHashedKey($key)] = $item;
            yield $item->getKey() => $item->get();
        }
    }

    /** {@inheritdoc} */
    protected function doSave(array $keysAndValues, int $expiry = 0): bool {
        if (empty($keysAndValues)) return true;
        $keys = array_keys($keysAndValues);
        $ttl = $expiry > 0 ? $expiry - time() : null;
        // reload data to save, not the fastest way,
        // here how we do it (as the provider item is never issued to the user)
        /** @var CacheItemInterface $item */
        foreach ($keys as $key) {
            $hKey = $this->getHashedKey($key);
            //Key can have been removed
            $item = $this->issued[$hKey] ?? $this->cacheProvider->getItem($key);
            unset($this->issued[$hKey]);
            $this->cacheProvider->saveDeferred(
                    $item
                            ->set($keysAndValues[$key])
                            ->expiresAfter($ttl)
            );
        }
        return $this->cacheProvider->commit();
    }

    /**
     * Cannot know what method to call for that
     * {@inheritdoc}
     */
    public function purge(): bool {
        return false;
    }

}
