<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheEntry, CacheError
};

class ApcuDriver extends BaseCacheDriver
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

    /**
     * Convenience function to convert expiry into TTL
     * A TTL/expiry of 0 never expires
     *
     *
     * @param int $expiry
     * @return int the ttl a negative ttl is already expired
     */
    protected function expiryToLifetime(int $expiry): int
    {
        static $max;
        $max = $max ?? apcu_cache_info(true)['ttl'];

        return
                $expiry !== 0 ?
                min($expiry - time(), $max) :
                0;
    }

    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    public function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    public function get(string $key): CacheEntry
    {
        $value = apcu_fetch($key, $success);
        if ($success === false) $value = CacheEntry::createEmpty($key);
        return $value;
    }

    public function has(string $key): bool
    {
        return apcu_exists($key);
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {
        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);
        if ($this->isExpired($expiry)) return $this->delete($key);
        return apcu_store($key, CacheEntry::create($key, $expiry, $value), $this->expiryToLifetime($expiry));
    }

}
