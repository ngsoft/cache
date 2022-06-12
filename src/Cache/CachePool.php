<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\{
    Cache\Events\CacheEvent, Cache\Events\CacheHit, Cache\Events\CacheMiss, Cache\Events\KeyDeleted, Cache\Events\KeySaved, Cache\Exceptions\InvalidArgument,
    Cache\Interfaces\CacheDriver, Cache\Interfaces\TaggableCacheItem, Cache\Utils\ExceptionLogger, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject,
    Traits\Unserializable
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

    /** @var Item[] */
    protected array $queue = [];

    public function __construct(
            protected CacheDriver $driver,
            string $prefix = '',
            int $defaultLifetime = 0,
            LoggerInterface $logger = null,
            protected ?EventDispatcherInterface $eventDispatcher = null,
    )
    {

        $this->setPrefix($prefix);

        $this->driver->setDefaultLifetime($defaultLifetime);

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
        $this->driver->setLogger($this->logger = $logger);
    }

    protected function isHit(Item $item): bool
    {
        if ($item->get() === null) {
            return false;
        }

        return $item->expiry === null || $item->expiry > microtime(true);
    }

    protected function expiryToLifetime(?int $expiry): ?int
    {
        if ($expiry !== null) {
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
     * @param string[]|string $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws InvalidArgument When $tags is not valid
     */
    public function invalidateTags(array|string $tags): bool
    {
        try {

            $tags = is_string($tags) ? [$tags] : $tags;
            return $this->driver->invalidateTag($tags);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function clear(): bool
    {
        return $this->driver->clear();
    }

    /** {@inheritdoc} */
    public function commit(): bool
    {
        try {

            $queue = $this->queue;
            $this->queue = [];

            $result = [];

            /** @var Item $item */
            foreach ($queue as $prefixed => $item) {

                if (!$this->isHit($item)) {
                    $result[] = $this->deleteItem($item->getKey());
                    continue;
                }
                $ttl = $this->expiryToLifetime($item->expiry);
                if ($result[] = $this->driver->set($prefixed, $item->get(), $ttl, $item->tags)) {
                    $this->dispatchEvent(new KeySaved($this, $item->getKey(), $item->get()));
                } else $this->deleteItem($item->getKey());
            }

            return $this->every(fn($bool) => $bool, $result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function deleteItem(string $key): bool
    {
        try {

            if ($this->driver->delete($this->getCacheKey($key))) {
                $this->dispatchEvent(new KeyDeleted($this, $key));
                return true;
            }
            return false;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function getItem(string $key): TaggableCacheItem
    {
        try {
            $prefixed = $this->getCacheKey($key);
            $cacheEntry = $this->driver->getCacheEntry($prefixed);

            if ($cacheEntry->isHit()) {
                $this->dispatchEvent(new CacheHit($this, $key, $cacheEntry->value));
            } else { $this->dispatchEvent(new CacheMiss($this, $key)); }

            return $cacheEntry->getCacheItem($key);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function getItems(array $keys = []): iterable
    {
        try {

            foreach ($keys as $key) {
                yield $key => $this->getItem($key);
            }
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function save(CacheItemInterface $item): bool
    {
        try {
            return $this->saveDeferred($item) && $this->commit();
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
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
