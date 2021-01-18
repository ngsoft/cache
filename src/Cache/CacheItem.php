<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Cache\TagInterop\TaggableCacheItemInterface,
    DateInterval,
    DateTime,
    DateTimeInterface,
    NGSOFT\Traits\Unserializable,
    Psr\Cache\CacheItemInterface,
    Symfony\Contracts\Cache\ItemInterface;
use function get_debug_type;

/**
 * A Basic Cache Item
 */
class CacheItem implements CacheItemInterface, TaggableCacheItemInterface, ItemInterface {

    use CacheUtils;
    use Unserializable;

    /**
     * Version Information
     */
    public const VERSION = CacheItemPool::VERSION;

    /** @var string */
    private $key;

    /** @var mixed */
    private $value = null;

    /** @var int|null */
    private $expiry = null;

    /** @var bool */
    private $tagAware = false;

    /**
     * @var string[] Tags saved with the entry
     */
    private $tags = [];

    /**
     * @var string[] New tags to be added
     */
    private $newTags = [];

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

    /** {@inheritdoc} */
    public function getPreviousTags() {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     * @return CacheItem
     */
    public function setTags(array $tags): TaggableCacheItemInterface {
        $this->tag($tags);
        return $this;
    }

    /**
     * {@inheritdoc}
     * @return ItemInterface|CacheItem
     */
    public function tag($tags): ItemInterface {
        if (!is_iterable($tags)) {
            $tags = [$tags];
        }
        if (count($tags) == 0) return $this;

        if (!$this->tagAware) throw new CacheException('Cache Pool is not Tag Aware, you cannot assign tags.');

        foreach ($tags as $tag) {
            $tag = $this->getValidTag($tag);
            $this->newTags[$tag] = $tag;
        }
        return $this;
    }

    /** {@inheritdoc} */
    public function getMetadata(): array {
        return [
            self::METADATA_CTIME => null,
            self::METADATA_EXPIRY => $this->expiry,
            self::METADATA_TAGS => $this->tags
        ];
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
     * Set the expiration timestamp
     * Why is it not in expiresAt() ?
     *
     * @internal private
     * @param int $time Timestamp
     * @return CacheItem
     */
    public function setExpiration(int $time): self {
        // prevents negative values (as 1 is already expired)
        $this->expiry = max(0, $time);
        return $this;
    }

    /**
     * Exports newly added tags
     * @internal private
     * @return string[]
     */
    public function getNewTags(): array {
        return $this->newTags;
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
        return $this->getMetadata();
    }

    /** {@inheritdoc} */
    public function __clone() {
        $this->tags = $this->newTags;
        $this->newTags = [];
    }

}
