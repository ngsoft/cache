<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use DateInterval,
    DateTime,
    DateTimeInterface;
use NGSOFT\{
    Cache, Traits\StringableObject, Traits\Unserializable
};
use Psr\Cache\CacheItemInterface,
    Stringable;
use function get_debug_type;

/**
 * A Cache Item
 */
final class Item implements CacheItemInterface, Cache, Stringable
{

    use Unserializable,
        StringableObject;

    public const RESERVED_CHAR_KEY = '{}()/\@:';

    public ?int $expiry = null;
    public mixed $value = null;
    public array $tags = [];

    public static function validateKey(mixed $key): void
    {


        if (!is_string($key)) {
            throw new InvalidArgument(sprintf(
                                    'Cache key must be a string, "%s" given.',
                                    get_debug_type($key)
            ));
        }
        if ('' === $key) {
            throw new InvalidArgument('Cache key length must be greater than zero.');
        }
        if (false !== strpbrk($key, self::RESERVED_CHAR_KEY)) {
            throw new InvalidArgument(sprintf(
                                    'Cache key "%s" contains reserved characters "%s".',
                                    $key,
                                    self::RESERVED_CHAR_KEY
            ));
        }
    }

    public static function create(string $key, mixed $value = null, ?int $expiry = null): static
    {
        $instance = new static($key);
        $instance->value = $value;
        $instance->expiry = $expiry;
        return $instance;
    }

    public function __construct(
            public readonly string $key
    )
    {
        static::validateKey($key);
    }

    /**
     * Adds a tag to a cache item.
     *
     * Tags are strings that follow the same validation rules as keys.
     *
     * @param string|string[] $tags A tag or array of tags
     *
     * @return $this
     *
     * @throws InvalidArgument  When $tag is not valid
     */
    public function tag(string|iterable $tags): static
    {
        $tags = is_string($tags) ? [$tags] : $tags;

        foreach ($tags as $tag) {
            static::validateKey($tag);
            $this->tags[$tag] = $tag;
        }
        return $this;
    }

    /** {@inheritdoc} */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if (is_null($time)) {
            $this->expiry = null;
        } elseif (is_int($time)) {
            $this->expiry = time() + $time;
        } else $this->expiry = (new DateTime())->add($time)->getTimestamp();


        return $this;
    }

    /** {@inheritdoc} */
    public function expiresAt(?DateTimeInterface $expiration): static
    {

        $this->expiry = !is_null($expiration) ? $expiration->getTimestamp() : $expiration;
        return $this;
    }

    /** {@inheritdoc} */
    public function get(): mixed
    {
        return $this->isHit() ? $this->value : null;
    }

    /** {@inheritdoc} */
    public function getKey(): string
    {
        return $this->key;
    }

    /** {@inheritdoc} */
    public function isHit(): bool
    {

        if ($this->value === null) {
            return false;
        }

        return $this->expiry === null || $this->expiry > microtime(true);
    }

    /** {@inheritdoc} */
    public function set(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /** {@inheritdoc} */
    public function __clone()
    {
        if (is_object($this->value)) {
            $this->value = clone $this->value;
        }
    }

}
