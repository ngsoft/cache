<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Countable,
    IteratorAggregate;
use NGSOFT\Cache\{
    CacheDriver, CacheEntry
};
use Traversable,
    ValueError;
use function get_debug_type;

class ChainDriver extends BaseCacheDriver implements Countable, IteratorAggregate
{

    /** @var CacheDriver[] */
    protected array $drivers = [];

    public function __construct(
            iterable $drivers,
            int $defaultLifetime = 0
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

        $this->setDefaultLifetime($defaultLifetime);
    }

    /** {@inheritdoc} */
    public function setDefaultLifetime(int $defaultLifetime): void
    {
        parent::setDefaultLifetime($defaultLifetime);
        foreach ($this as $driver) {
            $driver->setDefaultLifetime($this->defaultLifetime);
        }
    }

    /** {@inheritdoc} */
    public function count(): int
    {
        return count($this->drivers);
    }

    /** {@inheritdoc} */
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

    public function clear(): bool
    {
        $result = true;

        foreach ($this->drivers as $driver) {
            $result = $driver->clear() && $result;
        }
        return $result;
    }

    public function delete(string $key): bool
    {
        $result = true;

        foreach ($this->drivers as $driver) {
            $result = $driver->delete($key) && $result;
        }
        return $result;
    }

    public function get(string $key): CacheEntry
    {


        $result = new CacheEntry($key);
        foreach ($this->drivers as $index => $driver) {
            $result = $driver->get($key);

            if ($result->isHit()) {
                foreach ($this->getReverseIterator($index) as $i => $revDriver) {
                    $revDriver->set($key, $result->value, $result->expiry);
                }
                break;
            }
        }
        return $result;
    }

    public function has(string $key): bool
    {
        foreach ($this->drivers as $driver) {
            if ($driver->has($key)) {
                return true;
            }
        }
        return false;
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {

        $result = true;

        foreach ($this->drivers as $driver) {
            $result = $driver->set($key, $value, $expiry) && $result;
        }
        return $result;
    }

    public function purge(): void
    {
        foreach ($this->drivers as $driver) {
            $driver->purge();
        }
    }

}
