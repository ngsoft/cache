<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Closure;
use NGSOFT\{
    Cache, Cache\Events\CacheHit, Cache\Events\CacheMiss, Cache\Events\KeyDeleted, Cache\Events\KeySaved, Cache\Exceptions\InvalidArgument, Cache\Interfaces\CacheDriver,
    Cache\Interfaces\TaggableCacheItem, Cache\Utils\ExceptionLogger, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Lock\CacheLock, Lock\LockProvider, Lock\LockStore,
    Traits\DispatcherAware, Traits\StringableObject, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, Log\LoggerAwareInterface, Log\LoggerInterface
};
use Stringable,
    Throwable;
use function value;

/**
 * A PSR-6 cache pool
 */
class CachePool implements Stringable, LoggerAwareInterface, CacheItemPoolInterface, Cache, LockProvider
{

    use Unserializable,
        PrefixAble,
        ExceptionLogger,
        StringableObject,
        Toolkit,
        DispatcherAware;

    /** @var CacheItem[] */
    protected array $queue = [];
    protected int $defaultLifetime;

    public function __construct(
            CacheDriver $driver,
            string $prefix = '',
            int $defaultLifetime = 0,
            ?LoggerInterface $logger = null,
            ?EventDispatcherInterface $eventDispatcher = null,
    )
    {

        $this->driver = $driver;

        $this->setPrefix($prefix);

        $defaultLifetime = max(0, $defaultLifetime);
        $this->defaultLifetime = $defaultLifetime;

        if ($defaultLifetime > 0) {
            $this->driver->setDefaultLifetime($defaultLifetime);
        }

        if ($logger !== null) {
            $this->setLogger($logger);
        }

        if ($eventDispatcher !== null) {
            $this->eventDispatcher = $eventDispatcher;
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
     * Removes expired item entries if supported
     *
     * @return void
     */
    public function purge(): void
    {
        $this->driver->purge();
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
        if ( ! $item->isHit()) {
            $value = value($default);
            if ($value !== null) {
                $this->save($item->set($value));
            }
        }

        return $item->get();
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function increment(string $key, int $value = 1): int
    {

        try {
            $prefixed = $this->getCacheKey($key);
            unset($this->queue[$prefixed]);
            return $this->driver->increment($prefixed, $value);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function decrement(string $key, int $value = 1): int
    {

        try {
            $prefixed = $this->getCacheKey($key);
            unset($this->queue[$prefixed]);
            return $this->driver->decrement($prefixed, $value);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
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
        try {
            return ! $this->hasItem($key) && $this->save($this->getItem($key)->set(value($value)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function clear(): bool
    {
        $this->setPrefix($this->prefix);
        return $this->driver->clear();
    }

    /** {@inheritdoc} */
    public function commit(): bool
    {
        try {

            $queue = $this->queue;
            $this->queue = [];

            $result = true;

            /** @var CacheItem $item */
            foreach ($queue as $prefixed => $item) {

                if ( ! $this->isHit($item)) {

                    if ( ! $this->deleteItem($item->getKey())) {
                        $result = false;
                    }
                    continue;
                }
                $ttl = $this->expiryToLifetime($item->expiry);

                if ( ! $this->driver->set($prefixed, $item->get(), $ttl, $item->tags)) {
                    $result = false;
                    $this->deleteItem($item->getKey());
                } else { $this->dispatchEvent(new KeySaved($this, $item->getKey(), $item->get())); }
            }

            return $result;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function deleteItem(string $key): bool
    {
        try {

            unset($this->queue[$this->getCacheKey($key)]);
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

            $result = true;
            foreach ($keys as $key) {
                if ( ! $this->deleteItem($key)) {
                    $result = false;
                }
            }
            return $result;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function getItem(string $key): TaggableCacheItem
    {
        try {
            $prefixed = $this->getCacheKey($key);

            if (isset($this->queue[$prefixed])) {
                $item = clone $this->queue[$prefixed];
            } else { $item = $this->driver->getCacheEntry($prefixed)->getCacheItem($key); }

            if ($item->isHit()) {
                $this->dispatchEvent(new CacheHit($this, $key, $item->get()));
            } else { $this->dispatchEvent(new CacheMiss($this, $key)); }

            return $item;
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
            if ($item instanceof CacheItem) {
                $this->queue[$this->getCacheKey($item->getKey())] = $item;
                return true;
            }
            throw new InvalidArgument(sprintf(
                                    'Cache items are not transferable between pools. "%s" requested, "%s" given.',
                                    CacheItem::class,
                                    get_class($item)
            ));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function lock(string $name, int|float $seconds = 0, string $owner = ''): LockStore
    {
        return new CacheLock($this, $name, $seconds, $owner);
    }

}
