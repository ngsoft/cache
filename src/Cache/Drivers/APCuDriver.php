<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

/**
 * APCu Driver Implementation
 */
final class APCuDriver extends \NGSOFT\Cache\Utils\BaseDriver implements \NGSOFT\Cache\Driver {

    public function __construct() {

        if (
                !self::isSupported()
        ) throw new CacheException('APCu not enabled.');
    }

    /**
     * Checks if APCu is supported
     *
     * @staticvar bool $supported
     * @return bool
     */
    public static function isSupported(): bool {

        static $supported;

        if ($supported === null) {
            $supported = true;
            if (
                    !(function_exists('apcu_fetch') and
                    filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)) or
                    (PHP_SAPI === 'cli' and (int) ini_get('apc.enable_cli') !== 1)
            ) $supported = false;
        }

        return $supported;
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function clear(): bool {
        return apcu_clear_cache();
    }

    /** {@inheritdoc} */
    public function delete(string $key): bool {
        return apcu_delete($key);
    }

    /** {@inheritdoc} */
    public function get(string $key) {
        $value = apcu_fetch($key, $success);
        if ($success !== true) $value = null;
        return $value;
    }

    /** {@inheritdoc} */
    public function has(string $key): bool {
        return apcu_exists($key);
    }

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {
        if ($this->isExpired($expiry)) return $this->delete($key);
        $ttl = $this->expiryToLifetime($expiry);
        return apcu_store($key, $value, $ttl);
    }

}
