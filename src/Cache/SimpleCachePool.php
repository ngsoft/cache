<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use DateInterval;
use NGSOFT\Traits\{
    StringableObject, Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, Log\LoggerAwareInterface, SimpleCache\CacheInterface
};
use Stringable,
    Throwable;

/**
 * PSR-6 to PSR-16 Adapter
 */
final class SimpleCachePool implements CacheInterface, LoggerAwareInterface, Stringable
{

    use ExceptionLogger,
        Unserializable,
        StringableObject;

    /** @var CacheItemInterface[] */
    private array $items = [];

    public function __construct(private CacheItemPoolInterface $cachePool, private int $defaultLifetime = 0)
    {

    }

    /** {@inheritdoc} */
    public function getCachePool(): CacheItemPoolInterface
    {
        return $this->cachePool;
    }

    /** {@inheritdoc} */
    public function clear(): bool
    {
        $this->items = [];
        return $this->cachePool->clear();
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool
    {

        try {
            return $this->cachePool->deleteItem($key);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function deleteMultiple(iterable $keys): bool
    {
        try {
            $keys = is_array($keys) ? $keys : iterator_to_array($keys);
            return $this->cachePool->deleteItems($keys);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function get(string $key, mixed $default = null): mixed
    {

        try {
            $item = $this->items[$key] = $this->cachePool->getItem($key);
            return $item->isHit() ?
                    $item->get() :
                    $default;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {


        try {

            foreach ($keys as $key) {
                yield $key => $this->get($key, $default);
            }
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function has(string $key): bool
    {
        try {
            return $this->cachePool->hasItem($key);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        try {

            $ttl = $ttl ?? $this->defaultLifetime;

            $item = $this->items[$key] ?? $this->cachePool->getItem($key);
            unset($this->items[$key]);
            return $this->cachePool->save(
                            $item
                                    ->set($value)
                                    ->expiresAfter($ttl === 0 ? null : $ttl)
            );
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {

        try {
            $result = true;
            $ttl = $ttl ?? $this->defaultLifetime;
            foreach ($values as $key => $value) {
                $item = $this->items[$key] ?? $this->cachePool->getItem($key);
                unset($this->items[$key]);
                $result = $this->cachePool->saveDeferred($item->set($value)->expiresAfter($ttl === 0 ? null : $ttl)) && $result;
            }

            return $this->cachePool->commit() && $result;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function __debugInfo(): array
    {
        return [
            CacheItemPoolInterface::class => get_class($this->cachePool),
            'defaultLifetime' => $this->defaultLifetime,
        ];
    }

}
