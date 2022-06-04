<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\CacheEntry, DataStructure\SimpleObject
};

class JsonDriver extends BaseCacheDriver
{

    /** @var SimpleObject */
    protected SimpleObject $provider;

    public function __construct(
            protected string $file
    )
    {

        $this->provider = is_file($file) ? SimpleObject::fromJsonFile($file) : SimpleObject::create();
    }

    protected function update(): bool
    {
        return $this->provider->saveToJson($this->file);
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
        } catch (Throwable) { return null; } finally { restore_error_handler(); }
    }

    final protected function serializeEntry(mixed $value): mixed
    {
        try {
            $this->setErrorHandler();
            return is_object($value) || is_array($value) ? \serialize($value) : $value;
        } catch (Throwable) { return null; } finally { restore_error_handler(); }
    }

    public function purge(): void
    {

        foreach ($this->provider as $key => $entry) {
            if ($this->isExpired($entry['expiry'])) {
                unset($this->provider[$key]);
            }
        }
    }

    public function clear(): bool
    {

        $this->provider = SimpleObject::create();
        unlink($this->file);
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->provider[$this->getHashedKey($key)]);
        return $this->update();
    }

    public function get(string $key): CacheEntry
    {

        $this->purge();
        if ($entry = $this->provider[$this->getHashedKey($key)]) {
            $expiry = $entry['expiry'];
            $value = $this->unserializeEntry($entry['value']);
            if (!$this->isExpired()) return CacheEntry::create($key, $expiry, $value);
        }

        $this->delete($key);
        return CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->get($key)->isHit();
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {

        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($this->isExpired($expiry)) {
            return $this->delete($key);
        }

        $entry = [
            'expiry' => $expiry,
            'value' => $this->serializeEntry($value),
        ];

        $this->provider[$this->getHashedKey($key)] = $entry;
        return $this->update();
    }

}
