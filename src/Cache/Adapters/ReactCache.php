<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Closure;
use NGSOFT\{
    Cache, Cache\Exceptions\CacheError, Cache\Interfaces\CacheDriver, Cache\Utils\ExceptionLogger, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject,
    Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface
};
use React\{
    Cache\CacheInterface, Promise\PromiseInterface
};
use Stringable,
    Throwable;
use function NGSOFT\Tools\map;
use function React\Promise\{
    all, resolve
};

require_package('react/cache:^1.1', CacheInterface::class, CacheError::class);

final class ReactCache implements Cache, CacheInterface, Stringable, LoggerAwareInterface
{

    use Unserializable,
        StringableObject,
        PrefixAble,
        Toolkit,
        ExceptionLogger;

    protected ?LoggerInterface $logger = null;

    /**
     *
     * @param CacheDriver $driver
     * @param string $prefix
     * @param int $defaultLifetime
     */
    public function __construct(
            CacheDriver $driver,
            string $prefix = '',
            int $defaultLifetime = 0
    )
    {
        $this->driver = $driver;

        if ($defaultLifetime > 0) {
            $driver->setDefaultLifetime($defaultLifetime);
        }

        $this->setPrefix($prefix);
    }

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->driver->setLogger($this->logger = $logger);
    }

    protected function resolve(mixed $value): PromiseInterface
    {
        return resolve($value);
    }

    protected function all(mixed $values): PromiseInterface
    {
        return all($values);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return PromiseInterface<int> new value
     */
    public function increment(string $key, int $value = 1): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->increment($this->getCacheKey($key), $value));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return PromiseInterface<int> new value
     */
    public function decrement(string $key, int $value = 1): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->decrement($this->getCacheKey($key), $value));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * Adds data if it doesn't already exists
     *
     * @param string $key
     * @param mixed|Closure $value
     * @return PromiseInterface<bool> True if the data have been added, false otherwise
     */
    public function add(string $key, mixed $value): PromiseInterface
    {
        $resolveFalse = $this->resolve(false);
        $prefixed = $this->getCacheKey($key);
        if ($this->driver->has($prefixed)) {
            return $resolveFalse;
        }

        if ($value instanceof Closure) {
            $value = $value();
        }
        if ($value === null) {
            return $resolveFalse;
        }
        return $this->resolve($this->driver->set($prefixed, $value));
    }

    /** {@inheritdoc} */
    public function clear(): PromiseInterface
    {
        $this->setPrefix($this->prefix);
        return $this->resolve($this->driver->clear());
    }

    /** {@inheritdoc} */
    public function delete($key): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->delete($this->getCacheKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function has($key): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->has($this->getCacheKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function get($key, $default = null): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->get($this->getCacheKey($key), $default));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function set($key, $value, $ttl = null): PromiseInterface
    {
        try {

            if (null !== $ttl) {
                $ttl = (int) ceil($ttl);
            }

            return $this->resolve($this->driver->set($this->getCacheKey($key), $value, $ttl));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys): PromiseInterface
    {

        try {
            return $this->resolve($this->driver->deleteMany(array_map(fn($key) => $this->getCacheKey($key), $keys)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys, $default = null): PromiseInterface
    {

        try {

            $result = [];

            foreach ($keys as $key) {
                $result[$key] = $this->driver->get($this->getCacheKey($key), $default);
            }
            return $this->all($result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, $ttl = null): PromiseInterface
    {

        try {
            $callable = function ($val, &$key) {
                $key = $this->getCacheKey($key);
                return $val;
            };

            if (null !== $ttl) {
                $ttl = (int) ceil($ttl);
            }

            $prefixed = map($callable, $values);

            $result = $this->driver->setMany($prefixed, $ttl);

            return $this->resolve($result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

}
