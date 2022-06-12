<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use NGSOFT\{
    Cache, Cache\Exceptions\CacheError, Cache\Interfaces\CacheDriver, Cache\Utils\ExceptionLogger, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Tools, Traits\StringableObject,
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
use function React\Promise\{
    all, resolve
};

if (!interface_exists(CacheInterface::class)) {
    throw new CacheError('react/cache not installed, please run: composer require react/cache:^1.1');
}

class ReactCache implements Cache, CacheInterface, Stringable, LoggerAwareInterface
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

    public function clear(): PromiseInterface
    {
        $this->setPrefix($this->prefix);
        return $this->resolve($this->driver->clear());
    }

    public function delete($key): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->delete($this->getCacheKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function has($key): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->has($this->getCacheKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function get($key, $default = null): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->get($this->getCacheKey($key), $default));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function set($key, $value, $ttl = null): PromiseInterface
    {
        try {
            return $this->resolve($this->driver->set($this->getCacheKey($key), $value, $ttl));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function deleteMultiple(array $keys): PromiseInterface
    {

        try {
            return $this->resolve($this->driver->deleteMany(array_map(fn($key) => $this->getCacheKey($key), $keys)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function getMultiple(array $keys, $default = null): PromiseInterface
    {

        try {
            return $this->all($this->driver->getMany(array_map(fn($key) => $this->getCacheKey($key), $keys), $default));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function setMultiple(array $values, $ttl = null): PromiseInterface
    {

        try {

            $prefixed = Tools::map(function ($val, &$key) {
                        $key = $this->getCacheKey($key);
                        return $val;
                    }, $values);

            var_dump($prefixed);
            $result = $this->driver->setMany($prefixed, $ttl);

            return $this->resolve($result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

}
