<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheEntry, Exceptions\CacheError
};
use Throwable;

class ApcuDriver extends BaseDriver
{

    public static function isSupported(): bool
    {
        static $result;

        if ($result === null) {
            $result = false;

            if (\function_exists('apcu_fetch')) {
                $result = filter_var(ini_get('apc.enabled'), \FILTER_VALIDATE_BOOLEAN);
                if (php_sapi_name() === 'cli') {
                    $result = filter_var(ini_get('apc.enable_cli'), \FILTER_VALIDATE_INT) === 1 && $result;
                }
            }
        }

        return $result;
    }

    public function __construct()
    {

        if (!static::isSupported()) {
            throw new CacheError('APCu is not enabled.');
        }
        if (php_sapi_name() === 'cli') {

            $this->logger?->debug('APCu driver in CLI mode is the same as ArrayDriver.');
            ini_set('apc.use_request_time', 0);
        }
    }

    protected function getMaxLifetime(int $ttl): int
    {
        static $max;
        $max = $max ?? apcu_cache_info(true)['ttl'] ?? 3600;

        return
                $ttl !== 0 ?
                min($ttl, $max) :
                0;
    }

    protected function doSet(string $key, mixed $value, int $expiry, array $tags): bool
    {

        $ttl = $this->getMaxLifetime($this->expiryToLifetime($expiry));
        return apcu_store($key, $this->createEntry($value, $expiry, $tags), $ttl);
    }

    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    public function delete(string $key): bool
    {
        apcu_delete($key);

        return !$this->has($key);
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        return $this->createCacheEntry($key, apcu_fetch($key));
    }

    public function has(string $key): bool
    {
        return apcu_exists($key);
    }

}
