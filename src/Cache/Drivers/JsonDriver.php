<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\CacheEntry, DataStructure\SimpleObject
};

class JsonDriver extends BaseDriver
{

    protected SimpleObject $provider;

    public function __construct(
            protected string $file = '',
            protected string $key = 'cache'
    )
    {

        if (empty($file)) {
            $this->file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jsondriver.json';
        }

        $this->provider = SimpleObject::syncJsonFile($this->file);

        if (!isset($this->provider[$this->key])) {
            $this->provider[$this->key] = [];
        }
    }

    public function purge(): void
    {
        $cache = $this->provider[$this->key];

        foreach ($cache as $key => $entry) {
            if ($this->isExpired($entry[self::KEY_EXPIRY])) {
                unset($cache[$key]);
            }
        }
    }

    public function clear(): bool
    {
        $this->provider[$this->key] = [];
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->provider[$this->key][$key]);
        return true;
    }

    protected function doSet(string $key, mixed $value, int $expiry, array $tags): bool
    {

        $serialized = $this->serializeEntry($value);
        if ($serialized === null) {
            return false;
        }
        $this->provider[$this->key] [$key] = $this->createEntry($serialized, $expiry, $tags);
        return true;
    }

    public function getCacheEntry(string $key): CacheEntry
    {

        $entry = $this->provider[$this->key][$key]?->toArray();
        if (!is_null($entry)) {
            $value = $this->unserializeEntry($entry[self::KEY_VALUE]);

            return $this->createCacheEntry($key, [
                        self::KEY_EXPIRY => $entry[self::KEY_EXPIRY],
                        self::KEY_VALUE => $value,
                        self::KEY_TAGS => $entry[self::KEY_TAGS],
            ]);
        }

        return CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->getCacheEntry($key)->isHit();
    }

}
