<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\{
    Cache\Driver, Traits\Unserializable
};
use Traversable;

abstract class BaseDriver implements Driver {

    use CacheUtils;
    use Unserializable;

    /**
     * Char codes used by hash method
     */
    protected const HASH_CHARCODES = '0123456789abcdef';

    ////////////////////////////   GC (Override if possible)   ////////////////////////////

    /** {@inheritdoc} */
    public function purge(): bool {
        return false;
    }

    ////////////////////////////   Multi Operations (for drivers that don't support it, override them if they do)   ////////////////////////////

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys): bool {
        $r = true;
        $keys = array_values(array_unique($keys));
        foreach ($keys as $key) $r = $this->delete($key) && $r;
        return $r;
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys): Traversable {
        $keys = array_values(array_unique($keys));
        foreach ($keys as $key) yield $key => $this->get($key);
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, int $expiry = 0): bool {
        $r = true;
        if ($this->isExpired($expiry)) return $this->deleteMultiple(array_keys($values));
        foreach ($values as $key => $value) $r = $this->set($key, $value, $expiry) && $r;
        return $r;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Prevents Thowable inside classes __sleep or __serialize methods to interrupt operations
     *
     * @param mixed $input
     * @return string|null
     */
    final protected function safeSerialize($input): ?string {
        return Serializer::serialize($input);
    }

    /**
     * Prevents Thowable inside classes __wakeup or __unserialize methods to interrupt operations
     * Also the warning for wrong input
     *
     * @param string $input
     * @return mixed|null
     */
    final protected function safeUnserialize($input) {
        return Serializer::unserialize($input);
    }

    /**
     * Get a 32 Chars hashed key
     *
     * @param string $key
     * @return string
     */
    final protected function getHashedKey(string $key): string {
        // classname added to prevent conflicts on similar drivers
        // MD5 as we need speed and some filesystems are limited in length
        return hash('MD5', static::class . $key);
    }

    ////////////////////////////   Debug   ////////////////////////////

    /** {@inheritdoc} */
    public function jsonSerialize() {

        return [static::class => []];
    }

}
