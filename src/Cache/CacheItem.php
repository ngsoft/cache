<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use DateInterval,
    DateTime,
    DateTimeInterface;
use NGSOFT\{
    Cache, Cache\Utils\CacheUtils, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Log\LoggerAwareInterface
};
use Throwable;
use function get_debug_type;

/**
 * A Basic Cache Item
 */
class CacheItem implements CacheItemInterface, Cache, LoggerAwareInterface {

    use CacheUtils;
    use Unserializable;

    /** @var string */
    protected $key;

    /** @var mixed */
    protected $value = null;

    /** @var int|null */
    protected $expiry = null;

    /**
     * {@inheritdoc}
     * @return CacheItem
     */
    public function expiresAfter($time) {
        try {
            if ($time === null) $this->expiry = null;
            elseif ($time instanceof DateInterval) $this->expiry = (new DateTime())->add($time)->getTimestamp();
            elseif (is_int($time)) $this->expiry = time() + $time;
            else throw new InvalidArgumentException(sprintf('Expiration date must be an integer, a DateInterval or null, "%s" given.', get_debug_type($time)));
            return $this;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return CacheItem
     */
    public function expiresAt($expiration) {
        try {
            if ($expiration instanceof DateTimeInterface) {
                $this->expiry = $expiration->getTimestamp();
            } elseif ($expiration === null) $this->expiry = null;
            else throw new InvalidArgumentException(sprintf('Expiration date must implement DateTimeInterface or be null, "%s" given.', get_debug_type($expiration)));
            return $this;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return CacheItem
     */
    public function set($value) {
        $this->doCheckValue($value);
        $this->value = $value;
        return $this;
    }

    /** {@inheritdoc} */
    public function get() {
        return
                $this->isHit() ?
                $this->value :
                null;
    }

    /** {@inheritdoc} */
    public function getKey() {
        return $this->key;
    }

    /** {@inheritdoc} */
    public function isHit() {

        return
                $this->value !== null and
                !$this->isExpired($this->expiry);
    }

    /**
     * Exports Expiry Value
     * @internal private
     * @return int|null Timestamp
     */
    public function getExpiry(): ?int {
        return $this->expiry;
    }

    /**
     * Initialize a new CacheItem
     * @param string $key
     */
    public function __construct(string $key) {
        $this->key = $this->getValidKey($key);
    }

    /** {@inheritdoc} */
    public function __debugInfo() {
        return [
            'key' => $this->getKey(),
            'hit' => $this->isHit()
        ];
    }

    /**
     * Finds objects recursively inside an array and clones them
     * @param array $array
     * @return array
     */
    private function cloneRecursive(array $array): array {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_object($value)) $result[$key] = clone $value;
            elseif (is_array($value)) $result[$key] = $this->cloneRecursive($value);
            else $result[$key] = $value;
        }
        return $result;
    }

    /**
     * If in deferred with an Object(s) inside
     * to not modify the original objects
     */
    public function __clone() {
        if (is_object($this->value)) {
            $this->value = clone $this->value;
        } elseif (is_array($this->value)) {
            $this->value = $this->cloneRecursive($this->value);
        }
    }

}
