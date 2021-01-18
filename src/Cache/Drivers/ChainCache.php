<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Generator,
    IteratorAggregate;
use NGSOFT\{
    Cache\CacheDriver, Cache\CacheUtils, Cache\InvalidArgumentException, Traits\LoggerAware, Traits\Unserializable
};
use Traversable;
use function get_debug_type;

/**
 * Chain Cache Implementation
 */
class ChainCache implements CacheDriver, IteratorAggregate {

    use LoggerAware;
    use CacheUtils;
    use Unserializable;

    /** @var int */
    protected $defaultLifetime;

    /** @var CacheDriver[] */
    protected $drivers = [];

    /**
     * @param iterable|CacheDriver[] $drivers
     * @param int $defaultLifetime Lifetime used to save items on previous drivers when fetching data
     */
    public function __construct(
            iterable $drivers,
            int $defaultLifetime = 0
    ) {
        $this->setDefaultLifetime($defaultLifetime);

        foreach ($drivers as $driver) {
            if (!($driver instanceof CacheDriver)) {
                throw new InvalidArgumentException(sprintf('Invalid driver, %s required, %s given.', CacheDriver::class, get_debug_type($driver)));
            }
            $this->drivers[] = $driver;
        }
        if (count($this->drivers) == 0) {
            throw new InvalidArgumentException('$drivers invalid argument count, you need at least one driver.');
        }
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return [static::class => [$this->drivers]];
    }

    ////////////////////////////   The Engine   ////////////////////////////

    /** @return Generator|CacheDriver[] */
    public function getIterator() {
        foreach ($this->drivers as $driver) {
            yield $driver;
        }
    }

    /** @return Generator|CacheDriver[] */
    public function getReverseIterator(int $current = null) {
        $current = $current ?? count($this->drivers) - 1;
        if (!isset($this->drivers[$current])) return;
        for ($j = $current - 1; $j > -1; $j--) {
            yield $j => $this->drivers[$j];
        }
    }

    ////////////////////////////   API   ////////////////////////////

    /** @return int */
    public function getDefaultLifetime(): int {
        return $this->defaultLifetime;
    }

    /**
     * Set default lifeTime for downstream Drivers
     *
     * @param int $defaultLifetime
     */
    public function setDefaultLifetime(int $defaultLifetime) {
        $this->defaultLifetime = max(0, $defaultLifetime);
    }

    /** {@inheritdoc} */
    public function setNamespace(string $namespace): void {
        foreach ($this->getIterator() as $driver) $driver->setNamespace($namespace);
    }

    /** {@inheritdoc} */
    public function getNamespace(): string {
        return $this->drivers[0]->getNamespace();
    }

    /** {@inheritdoc} */
    public function clear(): bool {
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->clear() && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function deleteAll(): bool {
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->deleteAll() && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function purge(): bool {
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->purge() && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function contains(string $key): bool {
        foreach ($this->getIterator() as $driver) {
            if ($driver->contains($key)) return true;
        }
        return false;
    }

    /** {@inheritdoc} */
    public function delete(string ...$keys): bool {
        if (empty($keys)) return true;
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->delete(...$keys) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function save(array $keysAndValues, int $expiry = 0): bool {
        if (empty($keysAndValues)) return true;
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->save($keysAndValues, $expiry) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function fetch(string ...$keys): Traversable {
        if (empty($keys)) return;
        $keys = array_values(array_unique($keys));
        $missing = array_combine($keys, $keys);
        $values = $fetched = [];
        foreach ($this->getIterator() as $id => $driver) {
            if (empty($missing)) break;
            $items = [];
            $fetched = iterator_to_array($driver->fetch(...array_values($missing)));
            foreach ($missing as $key) {
                if ($fetched[$key] !== null) {
                    unset($missing[$key]);
                    $values[$key] = $fetched[$key];
                    if ($id > 0) $items[$key] = $fetched[$key];
                }
            }
            // save new items to previous drivers
            if (count($items) > 0) {
                foreach ($this->getReverseIterator($id) as $reverseDriver) {
                    $reverseDriver->save($items, $this->getDefaultLifetime());
                }
            }
        }
        //now iterate the results
        foreach ($keys as $key) yield $key => $values[$key] ?? null;
    }

}
