<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use DateInterval,
    DateTime,
    DateTimeInterface;
use NGSOFT\{
    Cache, Traits\StringableObject, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Log\LoggerAwareInterface, Log\LoggerAwareTrait
};
use Stringable;

final class Item implements CacheItemInterface, Cache, LoggerAwareInterface, Stringable
{

    use LoggerAwareTrait,
        Unserializable,
        StringableObject;

    public ?int $expiry = null;
    public mixed $value = null;

    public function __construct(
            public readonly string $key
    )
    {

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

}
