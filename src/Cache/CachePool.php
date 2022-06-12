<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Closure;
use NGSOFT\{
    Cache, Cache\Events\CacheEvent, Cache\Events\CacheHit, Cache\Events\CacheMiss, Cache\Events\KeyDeleted, Cache\Events\KeySaved, Cache\Exceptions\InvalidArgument,
    Cache\Interfaces\CacheDriver, Cache\Interfaces\TaggableCacheItem, Cache\Utils\ExceptionLogger, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject,
    Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, Log\LoggerAwareInterface, Log\LoggerInterface
};
use Stringable,
    Throwable;

class CachePool implements Stringable, LoggerAwareInterface, CacheItemPoolInterface, Cache
{

    use Unserializable,
        PrefixAble,
        ExceptionLogger,
        StringableObject,
        Toolkit;

    /** @var CacheItem[] */
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

    public function __debugInfo(): array
    {
        return [
            'prefix' => $this->prefix,
            'version' => $this->getPrefixVersion(),
            EventDispatcherInterface::class => $this->eventDispatcher ? get_class($this->eventDispatcher) : null,
            LoggerInterface::class => $this->logger ? get_class($this->logger) : null,
            CacheDriver::class => $this->driver,
        ];
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->driver->setLogger($this->logger = $logger);
    }

    protected function isHit(CacheItem $item): bool
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

    /**
     * Fetches a value from the pool or computes it if not found.
     *
     * @param string $key
     * @param mixed|Closure $default if set the item will be saved with that value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {

        $item = $this->getItem($key);
        if (!$item->isHit()) {
            if ($default instanceof Closure) {
                $value = $default($item);
            } else $value = $default;
            if ($value !== null) {
                $this->save($item->set($value));
            }
        }

        return $item->get();
    }

    /**
     * Increment a number under the key and return incremented value
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function increment(string $key, int $value = 1): int
    {
        return $this->driver->increment($this->getCacheKey($key), $value);
    }

    /**
     * Decrement a number under the key and return decremented value
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->driver->decrement($this->getCacheKey($key), $value);
    }

    /**
     * Adds data if it doesn't already exists
     *
     * @param string $key
     * @param mixed|Closure $value
     * @return bool True if the data have been added, false otherwise
     */
    public function add(string $key, mixed $value): bool
    {

        $item = $this->getItem($key);

        if ($item->isHit()) {
            return false;
        }

        if ($value instanceof Closure) {
            $value = $value($item);
        }
        if ($value === null) {
            return false;
        }

        return $this->save($item->set($value));
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

            /** @var CacheItem $item */
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
            if ($item instanceof CacheItem === false) {
                throw new InvalidArgument(sprintf(
                                        'Cache items are not transferable between pools. "%s" requested, "%s" given.',
                                        CacheItem::class,
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
