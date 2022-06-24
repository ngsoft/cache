<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Illuminate\{
    Cache\HasCacheLock, Contracts\Cache\LockProvider, Contracts\Cache\Store
};
use NGSOFT\{
    Cache, Cache\Exceptions\CacheError, Cache\Interfaces\CacheDriver, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject, Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface
};
use Stringable;
use function require_package;

require_package('illuminate/cache:^9.0', Store::class, CacheError::class);

final class LaravelStore implements Cache, Store, LoggerAwareInterface, Stringable, LockProvider
{

    use Unserializable,
        StringableObject,
        PrefixAble,
        Toolkit,
        HasCacheLock;

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

    /** {@inheritdoc} */
    public function increment($key, $value = 1): int
    {
        return $this->driver->increment($this->getCacheKey($key), $value);
    }

    /** {@inheritdoc} */
    public function decrement($key, $value = 1): int
    {
        return $this->driver->decrement($this->getCacheKey($key), $value);
    }

    /** {@inheritdoc} */
    public function flush(): bool
    {
        $this->setPrefix($this->prefix);
        return $this->driver->clear();
    }

    /** {@inheritdoc} */
    public function forever($key, $value): bool
    {
        return $this->driver->set($this->getCacheKey($key), $value);
    }

    /** {@inheritdoc} */
    public function forget($key): bool
    {
        return $this->driver->delete($this->getCacheKey($key));
    }

    /** {@inheritdoc} */
    public function get($key): mixed
    {
        return $this->driver->get($this->getCacheKey($key));
    }

    /** {@inheritdoc} */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /** {@inheritdoc} */
    public function many(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function put($key, $value, $seconds): bool
    {
        return $this->driver->set($this->getCacheKey($key), $value, $seconds);
    }

    /** {@inheritdoc} */
    public function putMany(array $values, $seconds): bool
    {

        $result = true;

        foreach ($values as $key => $value) {
            if ( ! $this->put($key, $value, $seconds)) {
                $result = false;
            }
        }
        return $result;
    }

}
