<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\Driver, Cache\Utils\BaseDriver, Tools\FixedArray
};
use Throwable;

/**
 * A Basic Cache of Cache
 */
final class ArrayDriver extends BaseDriver implements Driver {

    /**
     * References the minimum capacity (if != 0)
     */
    public const MIN_INDEX_CAPACITY = 32;

    /** @var bool */
    private $storeSerialized;

    /** @var int */
    private $capacity;

    /** @var int */
    private $maxLifeTime;

    /** @var array|FixedArray */
    private $expiries = [];

    /** @var array|FixedArray */
    private $values = [];

    /**
     * @param bool $storeSerialized Serialize the values before storing them
     * @param int $capacity Maximum items that can be stored in the cache, a value of 0 disables that feature(unlimited)
     * @param int $maxLifeTime Maximum number of seconds an item can stay in the cache,  a value of 0 disables that feature
     */
    public function __construct(
            bool $storeSerialized = true,
            int $capacity = 0,
            int $maxLifeTime = 0
    ) {

        $this->capacity = $capacity !== 0 ? max(self:: MIN_INDEX_CAPACITY, $capacity) : 0;
        $this->maxLifeTime = max(0, $maxLifeTime);
        $this->storeSerialized = $storeSerialized;
        $this->clear();
    }

    /** {@inheritdoc} */
    public function jsonSerialize(): mixed {

        return [static::class => [
                'store_serialized' => $this->storeSerialized,
                'max_capacity' => $this->capacity === 0 ? 'none' : $this->capacity,
                'entries' => count($this->values)
        ]];
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function clear(): bool {
        if ($this->capacity > 0) {
            $this->expiries = FixedArray::create($this->capacity);
            $this->values = FixedArray::create($this->capacity);
            $this->expiries->recursive = $this->values->recursive = false;
        } else $this->expiries = $this->values = [];
        return true;
    }

    /** {@inheritdoc} */
    public function purge(): bool {
        // clean up expired entries
        // can happen on long running scripts
        foreach ($this->expiries as $hKey => $expiry) {
            if ($this->isExpired($expiry)) unset($this->expiries[$hKey], $this->values[$hKey]);
        }
        return true;
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool {
        $key = $this->getHashedKey($key);
        unset($this->expiries[$key], $this->values[$key]);
        return true;
    }

    /** {@inheritdoc} */
    public function has(string $key): bool {
        // more memory efficient
        $this->purge();
        return !$this->isExpired($this->expiries[$this->getHashedKey($key)] ?? 1);
    }

    /** {@inheritdoc} */
    public function get(string $key) {
        return
                $this->has($key) ?
                ( $this->storeSerialized ?
                $this->unserializeIfNeeded($this->values[$this->getHashedKey($key)]) :
                $this->values[$this->getHashedKey($key)] ) :
                null;
    }

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {
        $expiry = $expiry === 0 ? PHP_INT_MAX : $expiry;
        if ($this->maxLifeTime > 0) $expiry = min($expiry, time() + $this->maxLifeTime);
        // expiry can be negative or < now
        if ($this->isExpired($expiry)) return $this->delete($key);
        $key = $this->getHashedKey($key);
        $this->expiries[$key] = $expiry;
        if ($this->storeSerialized) $this->values[$key] = $this->serializeIfNeeded($value);
        else $this->values[$key] = $value;
        return true;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Unserialize data if needed
     *
     * @param mixed $value
     * @return mixed
     */
    private function unserializeIfNeeded($value) {

        try {
            $this->setErrorHandler();
            if (
                    is_string($value) and
                    isset($value[2]) and
                    ':' === $value[1]
            ) {

                if (($value = \unserialize($value) ) === false) return null;
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
    private function serializeIfNeeded($value) {

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
    public function __clone(): void {
        // clone the fixed arrays
        if ($this->capacity > 0) {
            $this->values = clone $this->values;
            $this->expiries = clone $this->expiries;
        }
    }

}
