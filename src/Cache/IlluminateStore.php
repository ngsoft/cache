<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Illuminate\Contracts\Cache\Store,
    NGSOFT\Cache,
    RuntimeException;

if (!interface_exists(Store::class)) {
    throw new RuntimeException('illuminate/contracts not installed, please run: composer require illuminate/contracts:^9.0');
}

class IlluminateStore extends NamespaceAble implements Store, Cache
{

    public function flush(): bool
    {
        $this->clearNamespace();
        return $this->driver->clear();
    }

    public function forget($key): bool
    {
        return $this->driver->delete($this->getCacheKey($key));
    }

    public function get($key): mixed
    {
        return $this->driver->getRaw($this->getCacheKey($key));
    }

    public function getPrefix(): string
    {
        return '';
    }

    public function increment($key, $value = 1): int
    {
        $current = $this->get($key);
        // if value not int we use the value
        if (is_int($current)) {
            $value = $current + $value;
        }

        $this->forever($key, $value);
        return $value;
    }

    public function decrement($key, $value = 1): int
    {

        return $this->increment($key, $value * -1);
    }

    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->driver->getRaw($this->getCacheKey($key));
        }
        return $result;
    }

    public function forever($key, $value): bool
    {
        return $this->driver->set($this->getCacheKey($key), $value);
    }

    public function put($key, $value, $seconds): bool
    {
        return $this->driver->set($this->getCacheKey($key), $value, $this->lifeTimeToExpiry($seconds));
    }

    public function putMany(array $values, $seconds): bool
    {
        $result = true;

        foreach ($values as $key => $value) {
            $result = $this->put($key, $value, $seconds) && $result;
        }
        return $result;
    }

    protected function lifeTimeToExpiry(int $ttl): int
    {
        return $ttl === 0 ? $ttl : time() + $ttl;
    }

}
