<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Countable;
use NGSOFT\Cache\{
    CacheEntry, Interfaces\CacheDriver
};
use ValueError;
use function get_debug_type;

class ChainDriver extends BaseDriver implements Countable
{

    /** @var CacheDriver */
    protected array $drivers = [];

    public function __construct(
            iterable $drivers
    )
    {
        foreach ($drivers as $driver) {
            if ($driver instanceof CacheDriver === false) {
                throw new ValueError(sprintf('Driver of type "%s" is invalid.', get_debug_type($driver)));
            }

            if ($driver === $this) {
                throw new ValueError('Are you trying to crash your server?');
            }

            if (in_array($driver, $this->drivers, true)) {
                throw new ValueError('Cannot chain the same driver twice.');
            }

            $this->drivers[] = $driver;
        }

        if (count($this) === 0) {
            throw new ValueError('You need at least one driver.');
        }
    }

    public function count(): int
    {
        return count($this->drivers);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->drivers as $index => $driver) {
            yield $index => $driver;
        }
    }

    public function getReverseIterator(?int $current = null): Traversable
    {
        $current = $current ?? count($this);

        $previous = $current--;
        if (!isset($this->drivers[$previous])) {
            return;
        }

        for ($i = $previous; $i > -1; $i--) {
            yield $i => $this->drivers[$i];
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null, string|array $tags = []): bool
    {

        $tags = is_array($tags) ? $tags : [$tags];
        $expiry = $this->lifetimeToExpiry($ttl);

        if ($this->isExpired($expiry)) {
            return $this->delete($key);
        }

        $result = true;
        foreach ($this as $driver) {
            $result = $driver->set($key, $value, $ttl, $tags) && $result;
        }
        return $result;
    }

    protected function doSet(string $key, mixed $value, int $expiry, array $tags): bool
    {
        return false;
    }

    public function purge(): void
    {
        foreach ($this as $driver) {
            $driver->purge();
        }
    }

    public function clear(): bool
    {
        $result = true;

        foreach ($this as $driver) {
            $result = $driver->clear() && $result;
        }
        return $result;
    }

    public function delete(string $key): bool
    {
        $result = true;

        foreach ($this as $driver) {
            $result = $driver->delete($key) && $result;
        }
        return $result;
    }

    public function getCacheEntry(string $key): CacheEntry
    {

        $result = null;

        foreach ($this as $id => $driver) {

            $item = $driver->getCacheEntry($key);
            if ($item->isHit()) {
                $result = $item;

                foreach ($this->getReverseIterator($id) as $previous) {
                    $previous->set($key, $item->value, $this->expiryToLifetime($item->expiry), $item->tags);
                }

                break;
            }
        }
        return $result ?? CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {

        foreach ($this as $driver) {
            if ($driver->has($key)) {
                return true;
            }
        }
        return false;
    }

}
