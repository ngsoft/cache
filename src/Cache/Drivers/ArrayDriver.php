<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\DataStructure\FixedArray;

class ArrayDriver extends BaseCacheDriver
{

    protected const DEFAULT_SIZE = 255;

    protected int $size;
    protected FixedArray $expiries;
    protected FixedArray $entries;

    public function __construct(
            int $size = self::DEFAULT_SIZE,
            int $defaultLifetime = 0
    )
    {
        if ($size === 0) $this->size = PHP_INT_MAX;
        else $this->size = max(1, $size);
        $this->defaultLifetime = max(0, $defaultLifetime);
        $this->clear();
    }

    public function purge(): void
    {

        foreach ($this->expiries as $hashedKey => $expiry) {
            if ($this->isExpired($expiry)) {
                unset($this->expiries[$hashedKey], $this->entries[$hashedKey]);
            }
        }
    }

    public function clear(): bool
    {

        $this->expiries = FixedArray::create($this->size);
        $this->entries = FixedArray::create($this->size);
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->expiries[$this->getHashedKey($key)], $this->entries[$this->getHashedKey($key)]);
        return true;
    }

    public function get(string $key): mixed
    {
        if (!$this->has($key)) {
            return null;
        }
        return $this->unserializeEntry($this->entries[$this->getHashedKey($key)]);
    }

    public function has(string $key): bool
    {
        $this->purge();
        return !$this->isExpired($this->expiries[$this->getHashedKey($key)]);
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {

        $expiry = $expiry === 0 ? PHP_INT_MAX : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($expiry < 0) {
            return $this->delete($key);
        }
        $hashed = $this->getHashedKey($key);

        $this->expiries[$hashed] = $expiry;
        $this->entries[$hashed] = $this->serializeEntry($value);
        return true;
    }

    final protected function unserializeEntry(mixed $value): mixed
    {

        try {
            $this->setErrorHandler();
            if (!is_string($value) || !preg_match('#^[idbsaO]:#', $value)) {
                return $value;
            }

            if ($value === 'b:0;') {
                return false;
            }

            if (($result = \unserialize($value)) === false) {
                return null;
            }

            return $result;
        } catch (\Throwable) { return null; } finally { restore_error_handler(); }
    }

    final protected function serializeEntry(mixed $value): mixed
    {
        try {
            $this->setErrorHandler();
            return is_object($value) ? \serialize($value) : $value;
        } catch (\Throwable) { return null; } finally { restore_error_handler(); }
    }

}
