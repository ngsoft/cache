<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\{
    Cache\Events\CacheEvent, Cache\Events\KeyDeleted, Cache\Exceptions\InvalidArgument, Cache\Interfaces\CacheDriver, Cache\Interfaces\TaggableCacheItem,
    Cache\Utils\ExceptionLogger, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, Log\LoggerAwareInterface, Log\LoggerInterface
};
use Stringable,
    Throwable;

class CachePool implements Stringable, LoggerAwareInterface, CacheItemPoolInterface
{

    use Unserializable,
        PrefixAble,
        ExceptionLogger,
        StringableObject,
        Toolkit;

    protected array $queue = [];

    public function __construct(
            CacheDriver $driver,
            string $prefix = '',
            ?int $defaultLifeTime = null,
            LoggerInterface $logger = null,
            protected ?EventDispatcherInterface $eventDispatcher = null,
    )
    {
        parent::__construct($driver, $prefix);

        if ($defaultLifeTime !== null) {
            $this->driver->setDefaultLifetime($defaultLifetime);
        }

        if ($logger !== null) {
            $this->setLogger($logger);
        }
    }

    public function __destruct()
    {
        $this->commit();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        parent::setLogger($logger);
        $this->driver->setLogger($logger);
    }

    protected function isHit(Item $item): bool
    {
        if ($item->value === null) {
            return false;
        }

        return $item->expiry === null || $item->expiry > microtime(true);
    }

    protected function expiryToLifetime(?int $expiry): ?int
    {
        if ($expiry > 0) {
            return $expiry - time();
        }

        return $expiry;
    }

    protected function dispatchEvent(CacheEvent $event): CacheEvent
    {
        return $this->eventDispatcher?->dispatch($event) ?? $event;
    }

    /**
     * Invalidates cached items using tags.
     *
     * When implemented on a PSR-6 pool, invalidation should not apply
     * to deferred items. Instead, they should be committed as usual.
     * This allows replacing old tagged values by new ones without
     * race conditions.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws InvalidArgument When $tags is not valid
     */
    public function invalidateTags(array $tags): bool
    {
        try {
            return $this->driver->invalidateTag(array_map(fn($tag) => $this->getCacheKey($tag), $tags));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function clear(): bool
    {
        return $this->driver->clear();
    }

    public function commit(): bool
    {
        try {

        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function deleteItem(string $key): bool
    {
        try {

            if ($this->driver->delete($this->getCacheKey($key))) {
                $this->dispatchEvent(new KeyDeleted($key));
                return true;
            }
            return false;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function deleteItems(array $keys): bool
    {
        try {

            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $this->deleteItem($key);
            }
            return $this->every(fn($bool) => $bool, $result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function getItem(string $key): TaggableCacheItem
    {
        try {
            $prefixed = $this->getCacheKey($key);
            $cacheEntry = $this->driver->getCacheEntry($prefixed);

            if ($cacheEntry->isHit()) {
                $this->dispatchEvent(new Events\CacheHit($key, $cacheEntry->value));
            } else { $this->dispatchEvent(new Events\CacheMiss($key)); }
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function getItems(array $keys = []): iterable
    {
        try {

        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function hasItem(string $key): bool
    {
        try {
            $prefixed = $this->getCacheKey($key);
            if (isset($this->queue[$prefixed])) {
                $this->commit();
            }
            return $this->driver->has($prefixed);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function save(CacheItemInterface $item): bool
    {
        try {
            return $this->saveDeferred($item) && $this->commit();
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {

        try {
            if ($item instanceof Item === false) {
                throw new InvalidArgument(sprintf(
                                        'Cache items are not transferable between pools. "%s" requested, "%s" given.',
                                        Item::class,
                                        get_class($item)
                ));
            }

            $this->queue[$this->getCacheKey($item->getKey())] = $item;
            return true;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

}
