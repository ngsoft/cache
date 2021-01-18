<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\BaseDriver, Cache\CacheDriver, Tools\FixedArray
};
use Throwable,
    Traversable;

class ArrayCache extends BaseDriver implements CacheDriver {

    /**
     * References the default metacache capacity
     */
    public const MIN_INDEX_CAPACITY = 32;

    /** @var bool */
    protected $storeSerialized;

    /** @var int */
    protected $capacity;

    /** @var int */
    protected $maxLifeTime;

    /** @var array|FixedArray */
    protected $expiries = [];

    /** @var array|FixedArray */
    protected $values = [];

    /**
     * @param bool $storeSerialized Serialize the values before storing them
     * @param int $capacity Maximum items that can be stored in the cache, a value of 0 disables that feature
     * @param int $maxLifeTime Maximum number of seconds an item can stay in the cache,  a value of 0 disables that feature
     */
    public function __construct(
            bool $storeSerialized = true,
            int $capacity = 0,
            int $maxLifeTime = 0
    ) {

        $this->capacity = $capacity !== 0 ? max(self::MIN_INDEX_CAPACITY, $capacity) : 0;
        $this->maxLifeTime = max(0, $maxLifeTime);
        $this->storeSerialized = $storeSerialized;
        $this->doClear();
    }

    ////////////////////////////   Implementation   ////////////////////////////

    /** {@inheritdoc} */
    public function isSupported(): bool {

        return true;
    }

    /** {@inheritdoc} */
    protected function doClear(): bool {
        if ($this->capacity > 0) {
            $this->expiries = FixedArray::create($this->capacity);
            $this->values = FixedArray::create($this->capacity);
            $this->expiries->recursive = $this->values->recursive = false;
        } else $this->expiries = $this->values = [];
        return true;
    }

    /** {@inheritdoc} */
    protected function doContains(string $key): bool {
        $key = $this->getHashedKey($key);
        return isset($this->expiries[$key]) and
                !$this->isExpired($this->expiries[$key]);
    }

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        foreach ($keys as $key) {
            $key = $this->getHashedKey($key);
            unset($this->expiries[$key], $this->values[$key]);
        }
        return true;
    }

    /** {@inheritdoc} */
    protected function doFetch(string ...$keys): Traversable {
        foreach ($keys as $key) {
            if (!$this->doContains($key)) {
                $value = null;
            } elseif ($this->storeSerialized) $value = $this->unserializeIfNeeded($this->values[$this->getHashedKey($key)]);
            else $value = $this->values[$this->getHashedKey($key)];
            yield $key => $value;
        }
    }

    /** {@inheritdoc} */
    protected function doSave(array $keysAndValues, int $expiry = 0): bool {
        $r = true;
        foreach ($keysAndValues as $key => $value) {
            $this->doDelete($key);
            $hk = $this->getHashedKey($key);
            if ($this->storeSerialized) $value = $this->serializeIfNeeded($value);
            if ($value !== null) {
                $expiry = max(0, $expiry);
                //prevents isset return false
                if ($expiry === 0) $expiry = PHP_INT_MAX;
                $expiry = $this->maxLifeTime > 0 ? min(time() + $this->maxLifeTime, $expiry) : $expiry;
                $this->expiries[$hk] = $expiry;
                $this->values[$hk] = $value;
            } else $r = false;
        }
        return $r;
    }

    /** {@inheritdoc} */
    public function purge(): bool {

        foreach ($this->expiries as $key => $expiry) {
            if ($this->isExpired($expiry)) unset($this->expiries[$key], $this->values[$key]);
        }
        return true;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Unserialize data if needed
     *
     * @param mixed $value
     * @return mixed
     */
    protected function unserializeIfNeeded($value) {

        try {
            $this->setErrorHandler();
            if (
                    is_string($value) and
                    isset($value[2]) and
                    ':' === $value[1]
            ) {

                if (($value = \unserialize($value)) === false) return null;
            }
            return $value;
        } catch (Throwable $error) {
            return null;
        } finally {
            \restore_error_handler();
        }
    }

    /**
     * Serialize data if needed
     *
     * @param mixed $value
     * @return mixed
     */
    protected function serializeIfNeeded($value) {

        try {
            $this->setErrorHandler();
            if (is_string($value)) {
                if (isset($value[2]) and ':' === $value[1]) {
                    return \serialize($value);
                }
            } elseif (!is_scalar($value)) {
                $serialized = \serialize($value);
                if ('C' === $serialized[0] || 'O' === $serialized[0] || preg_match('/;[OCRr]:[1-9]/', $serialized)) {
                    return $serialized;
                }
            }

            return $value;
        } catch (Throwable $error) {
            return null;
        } finally {
            \restore_error_handler();
        }
    }

    /** {@inheritdoc} */
    public function __clone() {
        // clone the fixed arrays for tag aware
        if ($this->capacity > 0) {
            $this->values = clone $this->values;
            $this->expiries = clone $this->expiries;
        }
    }

    /** {@inheritdoc} */
    public function __debugInfo() {
        return [];
    }

}
