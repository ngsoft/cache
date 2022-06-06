<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\CacheEntry, DataStructure\SimpleObject
};
use Throwable;

/**
 * Saves cache data into a Json file
 */
class JsonDriver extends BaseCacheDriver
{

    /** @var SimpleObject */
    protected SimpleObject $provider;

    public function __construct(
            protected string $file = '',
            protected string $key = 'cache'
    )
    {

        $this->file = empty($file) ?
                sys_get_temp_dir() .
                DIRECTORY_SEPARATOR .
                strtolower(substr(static::class, 1 + strrpos(static::class, '\\')))
                . '.json' :
                $file;

        $this->provider = is_file($this->file) ? SimpleObject::fromJsonFile($this->file) : SimpleObject::create();

        if (!isset($this->provider[$this->key])) {
            $this->provider[$this->key] = [];
        }
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

        foreach ($this->provider[$this->key] as $key => $entry) {

            if ($this->isExpired($entry['expiry'])) {
                unset($this->provider[$this->key][$key]);
            }
        }
    }

    public function clear(): bool
    {
        $this->provider[$this->key] = [];
        return $this->update();
    }

    public function delete(string $key): bool
    {
        unset($this->provider[$this->key][$this->getHashedKey($key)]);
        return $this->update();
    }

    public function get(string $key): CacheEntry
    {

        $this->purge();
        if ($entry = $this->provider[$this->key][$this->getHashedKey($key)]) {
            $expiry = $entry['expiry'];
            $value = $this->unserializeEntry($entry['value']);
            if (!$this->isExpired($expiry)) {
                return CacheEntry::create($key, $expiry, $value);
            }
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

        if ($this->isExpired($expiry) || $value === null) {
            return $this->delete($key);
        }

        $entry = [
            'expiry' => $expiry,
            'value' => $this->serializeEntry($value),
        ];

        $this->provider[$this->key][$this->getHashedKey($key)] = $entry;
        return $this->update();
    }

}
