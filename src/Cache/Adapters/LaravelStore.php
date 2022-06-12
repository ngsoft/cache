<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Illuminate\Contracts\Cache\Store;
use NGSOFT\{
    Cache, Cache\Exceptions\CacheError, Cache\Utils\ExceptionLogger, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject, Traits\Unserializable
};
use Psr\Log\LoggerAwareInterface;

if (!interface_exists(Store::class)) {
    throw new CacheError('illuminate/contracts not installed, please run: composer require illuminate/contracts:^9.0');
}

class LaravelStore implements Cache, Store, LoggerAwareInterface
{

    use Unserializable,
        ExceptionLogger,
        StringableObject,
        PrefixAble,
        Toolkit;

    public function increment($key, $value = 1): int
    {
        return $this->driver->increment($this->getCacheKey($key), $value);
    }

    public function decrement($key, $value = 1): int
    {
        return $this->driver->decrement($this->getCacheKey($key), $value);
    }

    public function flush(): bool
    {
        $this->setPrefix($this->prefix);
        return $this->driver->clear();
    }

    public function forever($key, $value): bool
    {
        return $this->driver->set($this->getCacheKey($key), $value);
    }

    public function forget($key): bool
    {
        return $this->driver->delete($this->getCacheKey($key));
    }

    public function get($key): mixed
    {
        return $this->driver->get($this->getCacheKey($key));
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function many(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function put($key, $value, $seconds): bool
    {
        return $this->driver->set($this->getCacheKey($key), $value, $seconds);
    }

    public function putMany(array $values, $seconds): bool
    {

        $result = [];

        foreach ($values as $key => $value) {
            $result[] = $this->put($key, $value, $seconds);
        }
        return $this->every(fn($bool) => $bool, $result);
    }

}
