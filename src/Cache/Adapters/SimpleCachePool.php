<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Closure,
    DateInterval;
use NGSOFT\{
    Cache, Cache\Utils\ExceptionLogger, Traits\StringableObject, Traits\Unserializable
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
            $this->items[$key] = $this->items[$key] ?? $this->cachePool->getItem($key);
            $item = $this->items[$key];

            if ($item->isHit()) {
                return $item->get();
            }
            if ($default instanceof Closure) {
                $save = true;
                $value = $default($save);
                if ($save === true && !is_null($value)) {
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
