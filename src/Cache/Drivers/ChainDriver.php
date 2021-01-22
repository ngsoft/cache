<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Countable,
    Generator,
    IteratorAggregate;
use NGSOFT\{
    Cache\Driver, Cache\InvalidArgumentException, Cache\Utils\CacheUtils, Traits\Unserializable
};
use Traversable;
use function get_debug_type;

/**
 * Chain Cache Implementation
 */
final class ChainDriver implements Driver, IteratorAggregate, Countable {

    use CacheUtils;
    use Unserializable;

    /** @var int */
    protected $defaultLifetime;

    /** @var Driver[] */
    protected $drivers = [];

    /**
     * @param iterable|Driver[] $drivers
     * @param int $defaultLifetime Lifetime used to save items on previous drivers when fetching data
     */
    public function __construct(
            iterable $drivers,
            int $defaultLifetime = 0
    ) {

        foreach ($drivers as $driver) {
            if (!($driver instanceof Driver)) {
                throw new InvalidArgumentException(sprintf('Invalid driver, %s required, %s given.', Driver::class, get_debug_type($driver)));
            }
            if ($driver === $this) {
                throw new InvalidArgumentException('Are you trying to crash your server?');
            }
            $this->drivers[] = $driver;
        }
        if (count($this->drivers) == 0) {
            throw new InvalidArgumentException('You need at least one driver.');
        }
        $this->setDefaultLifetime($defaultLifetime);
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return [static::class => [$this->drivers]];
    }

    ////////////////////////////   The Engine   ////////////////////////////

    /** @return Generator|Driver[] */
    public function getIterator() {
        foreach ($this->drivers as $id => $driver) {
            yield $id => $driver;
        }
    }

    /** @return Generator|Driver[] */
    public function getReverseIterator(int $current = null) {
        if ($current === null) {
            $current = count($this->drivers);
        } elseif (!isset($this->drivers[$current])) return;
        for ($j = $current - 1; $j > -1; $j--) {
            yield $j => $this->drivers[$j];
        }
    }

    /** {@inheritdoc} */
    public function count() {
        return count($this->drivers);
    }

    ////////////////////////////   API   ////////////////////////////

    /** @return int */
    public function getDefaultLifetime(): int {
        return $this->defaultLifetime;
    }

    /**
     * Set default lifeTime for downstream Drivers
     *
     * @suppress PhanUndeclaredMethod
     * @param int $defaultLifetime
     */
    public function setDefaultLifetime(int $defaultLifetime) {
        $this->defaultLifetime = max(0, $defaultLifetime);
        // chain inside chain? (it's not forbidden, just don't use the same driver twice)
        foreach ($this->getIterator() as $driver) {
            if (method_exists($driver, 'setDefaultLifetime')) {
                $driver->setDefaultLifetime($this->defaultLifetime);
            }
        }
    }

    /** {@inheritdoc} */
    public function clear(): bool {
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->clear() && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool {
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->delete($key) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys): bool {
        $r = true;
        $keys = array_values(array_unique($keys));
        foreach ($this->getIterator() as $driver) $r = $driver->deleteMultiple($keys) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function has(string $key): bool {
        foreach ($this->getIterator() as $driver) {
            if ($driver->has($key)) return true;
        }
        return false;
    }

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->set($key, $value, $expiry) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, int $expiry = 0): bool {
        $r = true;
        foreach ($this->getIterator() as $driver) $r = $driver->setMultiple($values, $expiry) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function get(string $key) {
        foreach ($this->getMultiple([$key]) as $value) return $value;
        // phan, again ...
        return null;
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys): Traversable {
        // previously that was easy, now ...
        if (empty($keys)) return;
        $keys = array_values(array_unique($keys));
        $missing = array_combine($keys, $keys);
        $values = [];

        foreach ($this->getIterator() as $id => $driver) {
            if (empty($missing)) break;
            //keeps track of the new entries
            $items = [];
            foreach ($driver->getMultiple($missing) as $key => $value) {
                if ($value !== null) {
                    unset($missing[$key]);
                    $values[$key] = $value;
                    if ($id > 0) $items[$key] = $value;
                }
            }
            // save new items to previous drivers (incremental/downstream)
            if (count($items) > 0) {
                foreach ($this->getReverseIterator($id) as $reverseDriver) {
                    // expiry for downstream drivers
                    // (chain is nice but we have no other way, as an expired item can be issued indefinitely
                    // so put volatile cache first in chain and slower(filesystem) cache last in chain)
                    $expiry = $this->defaultLifetime > 0 ? time() + $this->defaultLifetime : 0;
                    $reverseDriver->setMultiple($items, $expiry);
                }
            }
        }
        //now iterate the results
        foreach ($keys as $key) yield $key => $values[$key] ?? null;
    }

}
