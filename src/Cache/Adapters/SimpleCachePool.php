<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Closure,
    DateInterval;
use NGSOFT\{
    Cache, Cache\CachePool, Cache\Utils\ExceptionLogger, Traits\StringableObject, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, Log\LoggerAwareInterface, SimpleCache\CacheInterface
};
use Stringable,
    Throwable;

/**
 * PSR-6 to PSR-16 Adapter
 */
final class SimpleCachePool implements CacheInterface, LoggerAwareInterface, Stringable, Cache
{

    use ExceptionLogger,
        Unserializable,
        StringableObject;

    /** @var CacheItemInterface[] */
    private array $items = [];

    public function __construct(private CacheItemPoolInterface $cachePool, private ?int $defaultLifetime = null)
    {

    }

    protected function pullItem(string $key): CacheItemInterface
    {
        $item = $this->items[$key] ?? $this->cachePool->getItem($key);
        unset($this->items[$key]);
        return $item;
    }

    /** {@inheritdoc} */
    public function getCachePool(): CacheItemPoolInterface
    {
        return $this->cachePool;
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

            if ($this->cachePool instanceof CachePool) {
                return $this->cachePool->increment($key, $value);
            }


            $current = $this->get($key);

            if (is_int($current)) {
                $value += $current;
            }
            $this->set($key, $value);
            return $value;
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
            return $this->increment($key, $value * -1);
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
            if ($this->cachePool instanceof CachePool) {
                return $this->cachePool->add($key, $value);
            }

            return ! $this->has($key) && $this->set($key, value($value));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
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

    /**
     * {@inheritdoc}
     * @phan-suppress PhanSuspiciousValueComparison
     */
    public function get(string $key, mixed $default = null): mixed
    {

        try {
            $item = $this->items[$key] ??= $this->cachePool->getItem($key);

            if ($item->isHit()) {
                return $item->get();
            }

            if ($default instanceof Closure) {
                $save = true;
                $value = $default($save);
                if ($save === true && ! is_null($value)) {
                    $this->set($key, $value);
                }
                return $value;
            }
            return $default;
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
            return $this->cachePool->save(
                            $this->pullItem($key)
                                    ->set($value)
                                    ->expiresAfter($ttl ?? $this->defaultLifetime)
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

            foreach ($values as $key => $value) {
                $result = $this->cachePool->saveDeferred(
                                $this
                                        ->pullItem($key)
                                        ->set($value)
                                        ->expiresAfter($ttl ?? $this->defaultLifetime)
                        ) && $result;
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
        ];
    }

}
