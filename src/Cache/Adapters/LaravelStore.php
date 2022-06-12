<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Illuminate\Contracts\Cache\Store;
use NGSOFT\{
    Cache, Cache\Exceptions\CacheError, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject, Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface
};
use Stringable;

if (!interface_exists(Store::class)) {
    throw new CacheError('illuminate/contracts not installed, please run: composer require illuminate/contracts:^9.0');
}

class LaravelStore implements Cache, Store, LoggerAwareInterface, Stringable
{

    use Unserializable,
        StringableObject,
        PrefixAble,
        Toolkit;

    protected ?LoggerInterface $logger = null;

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

        $result = [];

        foreach ($values as $key => $value) {
            $result[] = $this->put($key, $value, $seconds);
        }
        return $this->every(fn($bool) => $bool, $result);
    }

}
