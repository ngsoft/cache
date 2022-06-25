<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use Countable;
use NGSOFT\{
    Cache\CacheEntry, DataStructure\JsonObject, Filesystem\File, Tools
};

/**
 * A driver that can be used for Cli applications
 * Can store data inside a json config file for example
 */
class JsonDriver extends BaseDriver implements Countable
{

    protected JsonObject $provider;
    protected string $file;

    /**
     * @param string|File $file
     * @param string $key Key to use inside the object
     */
    public function __construct(
            string|File $file = '',
            protected string $key = 'cache'
    )
    {

        if (empty($file)) {
            $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jsondriver.json';
        }
        $this->file = (string) $file;

        $this->provider = JsonObject::fromJsonFile($this->file);

        if ( ! isset($this->provider[$this->key])) {
            $this->provider[$this->key] = [];
        }
    }

    public function purge(): void
    {
        $cache = &$this->provider[$this->key];

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

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {

        $serialized = $this->serializeEntry($value);
        if ($serialized === null) {
            return false;
        }
        $this->provider[$this->key] [$key] = $this->createEntry($serialized, $this->lifetimeToExpiry($ttl), $tags);
        return true;
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        $this->purge();
        $entry = $this->provider[$this->key][$key]?->toArray();
        if ( ! is_null($entry)) {
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

    public function count(): int
    {
        return count($this->provider[$this->key] ?? []);
    }

    public function __debugInfo(): array
    {
        return [
            'defaultLifetime' => $this->defaultLifetime,
            $this->file . "[{$this->key}]" => [
                'File Size' => Tools::getFilesize(filesize($this->file) ?: 0),
                'Cache Entries' => count($this->provider[$this->key]),
            ]
        ];
    }

}
