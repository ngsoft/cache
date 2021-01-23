<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheItemPool, Driver, InvalidArgumentException, Utils\BaseDriver
};
use Psr\Cache\{
    CacheItemInterface, CacheItemPoolInterface
};
use Traversable;

/**
 * Driver for using a PSR6 Cache
 */
final class CachePoolDriver extends BaseDriver implements Driver {

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
                $cacheProvider instanceof CacheItemPool
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

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return [
            static::class => [
                CacheItemPoolInterface::class => get_class($this->cacheProvider)
            ]
        ];
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function clear(): bool {
        return $this->cacheProvider->clear();
    }

    /** {@inheritdoc} */
    public function has(string $key): bool {
        return $this->cacheProvider->hasItem($key);
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool {
        unset($this->issued[$this->getHashedKey($key)]);
        return $this->cacheProvider->deleteItem($key);
    }

    /** {@inheritdoc} */
    public function get(string $key) {
        $item = $this->issued[$this->getHashedKey($key)] = $this->cacheProvider->getItem($key);
        return $item->isHit() ? $item->get() : null;
    }

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {
        if ($this->isExpired($expiry)) return $this->delete($key);
        $lifetime = $this->expiryToLifetime($expiry);
        if ($lifetime === 0) $lifetime = null;
        $hkey = $this->getHashedKey($key);
        /** @var CacheItemInterface $item */
        $item = $this->issued[$hkey] ?? $this->cacheProvider->getItem($key);
        unset($this->issued[$hkey]);
        return $this->cacheProvider->save($item->set($value)->expiresAfter($lifetime));
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys): bool {
        if (empty($keys)) return true;
        foreach ($keys as $key) unset($this->issued[$this->getHashedKey($key)]);
        return $this->cacheProvider->deleteItems($keys);
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys): Traversable {
        if (empty($keys)) return;
        foreach ($this->cacheProvider->getItems($keys)as $key => $item) {
            $this->issued[$this->getHashedKey($key)] = $item;
            yield $key => $item->isHit() ? $item->get() : null;
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, int $expiry = 0): bool {
        if (empty($values)) return true;
        if ($this->isExpired($expiry)) return $this->deleteMultiple(array_keys($values));
        $lifetime = $this->expiryToLifetime($expiry);
        if ($lifetime === 0) $lifetime = null;
        /** @var CacheItemInterface $item */
        foreach ($values as $key => $value) {
            $hkey = $this->getHashedKey($key);
            $item = $this->issued[$hkey] ?? $this->cacheProvider->getItem($key);
            unset($this->issued[$hkey]);
            $this->cacheProvider->saveDeferred($item->set($value)->expiresAfter($lifetime));
        }
        return $this->cacheProvider->commit();
    }

}
