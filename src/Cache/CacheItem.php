<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Cache\TagInterop\TaggableCacheItemInterface,
    DateInterval,
    DateTime,
    DateTimeInterface;
use NGSOFT\{
    Cache\Utils\CacheUtils, Traits\Unserializable
};
use Psr\Cache\CacheItemInterface,
    Symfony\Contracts\Cache\ItemInterface;
use function get_debug_type;

/**
 * A Basic Cache Item
 */
class CacheItem implements CacheItemInterface, \NGSOFT\Cache {

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
        if ($time === null) $this->expiry = null;
        elseif ($time instanceof DateInterval) $this->expiry = (new DateTime())->add($time)->getTimestamp();
        elseif (is_int($time)) $this->expiry = time() + $time;
        else throw new InvalidArgumentException(sprintf('Expiration date must be an integer, a DateInterval or null, "%s" given.', get_debug_type($time)));
        return $this;
    }

    /**
     * {@inheritdoc}
     * @return CacheItem
     */
    public function expiresAt($expiration) {
        if ($expiration instanceof DateTimeInterface) {
            $this->expiry = $expiration->getTimestamp();
        } elseif ($expiration === null) $this->expiry = null;
        else throw new InvalidArgumentException(sprintf('Expiration date must implement DateTimeInterface or be null, "%s" given.', get_debug_type($expiration)));
        return $this;
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
    public function getExpiration(): ?int {
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

}
