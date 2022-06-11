<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use DateInterval,
    DateTime,
    DateTimeInterface;
use NGSOFT\{
    Cache, Cache\Exceptions\InvalidArgument, Cache\Interfaces\TaggableCacheItem, Traits\StringableObject, Traits\Unserializable
};
use Stringable;
use function get_debug_type;

/**
 * A Cache Item
 */
final class Item implements TaggableCacheItem, Cache, Stringable
{

    use Unserializable,
        StringableObject;

    public const RESERVED_CHAR_KEY = '{}()/\@:';

    public ?int $expiry = null;
    public mixed $value = null;
    public array $tags = [];
    protected array $metadata = [];
    protected ?bool $hit = null;

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

    public static function create(string $key, ?array $metadata = null): static
    {
        $instance = new static($key, $metadata);
        return $instance;
    }

    public function __construct(
            public readonly string $key,
            ?array $metadata = null
    )
    {
        static::validateKey($key);
        $this->metadata = $metadata ?? [
            self::METADATA_EXPIRY => null,
            self::METADATA_TAGS => [],
            self::METADATA_VALUE => null,
        ];

        if ($this->isHit()) {
            $this->value = $this->metadata[self::METADATA_VALUE];
        }
    }

    /** {@inheritdoc} */
    public function tag(string|iterable $tags): static
    {
        $tags = is_string($tags) ? [$tags] : $tags;

        foreach ($tags as $tag) {
            static::validateKey($tag);
            $this->tags[$tag] = $tag;
        }
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
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
        return $this->value;
    }

    /** {@inheritdoc} */
    public function getKey(): string
    {
        return $this->key;
    }

    /** {@inheritdoc} */
    public function isHit(): bool
    {
        if (null === $this->hit) {
            if ($this->metadata[self::METADATA_VALUE] !== null) {
                $this->hit = false;
            } else $this->hit = $this->metadata[self::METADATA_EXPIRY] === null || $this->metadata[self::METADATA_EXPIRY] > microtime(true);
        }

        return $this->hit;
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

    public function __debugInfo(): array
    {
        return [
            'key' => $this->key,
            'expiry' => $this->expiry,
            'valueType' => get_debug_type($this->get()),
            'hit' => $this->isHit(),
        ];
    }

}
