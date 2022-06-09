<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\Cache;
use React\{
    Cache\CacheInterface, Promise\PromiseInterface
};
use Throwable;
use function React\Promise\resolve;

if (!interface_exists(CacheInterface::class)) {

    throw new \RuntimeException('react/cache not installed, please run: composer require react/cache:^1.1');
}

final class ReactCache extends NamespaceAble implements Cache, CacheInterface
{

    public function __construct(TaggedCacheDriver|CacheDriver $driver, protected int $defaultLifetime = 0, string $namespace = '')
    {
        parent::__construct($driver, $namespace);
        $this->driver->setDefaultLifetime($defaultLifetime);
    }

    /** {@inheritdoc} */
    public function clear()
    {
        $this->clearNamespace();
        return $this->resolve($this->driver->clear());
    }

    /** {@inheritdoc} */
    public function delete($key)
    {

        try {

            return $this->resolve($this->driver->delete($this->getCacheKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys)
    {
        try {
            $result = true;
            foreach ($keys as $key) {
                $result = $this->driver->delete($this->getCacheKey($key)) && $result;
            }
            return $this->resolve($result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function get($key, $default = null)
    {
        try {
            return $this->resolve($this->driver->getRaw($this->getCacheKey($key)) ?? $default);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys, $default = null)
    {
        try {
            $result = [];

            foreach ($keys as $key) {
                $result[$key] = $this->driver->getRaw($this->getCacheKey($key)) ?? $default;
            }
            return $this->resolve($result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function has($key)
    {
        try {
            return $this->resolve($this->driver->has($this->getCacheKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function set($key, $value, $ttl = null)
    {
        try {
            return $this->resolve($this->driver->set($this->getCacheKey($key), $value, $this->lifeTimeToExpiry($ttl)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, $ttl = null)
    {
        try {

            $result = true;

            $expiry = $this->lifeTimeToExpiry($ttl);
            foreach ($values as $key => $value) {
                $result = $this->driver->set($this->getCacheKey($key), $value, $expiry) && $result;
            }

            return $this->resolve($result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    protected function resolve(mixed $value): PromiseInterface
    {
        return resolve($value);
    }

    protected function lifeTimeToExpiry(float|int|null $ttl): int
    {

        if (is_float($ttl)) $ttl = (int) ceil($ttl);
        elseif (is_null($ttl)) $ttl = $this->defaultLifetime;
        return $ttl === 0 ? 0 : (time() + $ttl);
    }

}
