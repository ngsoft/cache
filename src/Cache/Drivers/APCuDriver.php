<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheDriver, CacheException, Utils\BaseDriver
};
use Traversable;

class APCuDriver extends BaseDriver implements CacheDriver {

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
    protected function doClear(): bool {
        return apcu_clear_cache();
    }

    /**
     * APCu Auto removes expired items
     *
     * @return bool
     */
    public function purge(): bool {
        return true;
    }

    /** {@inheritdoc} */
    protected function doContains(string $key): bool {
        return apcu_exists($key);
    }

    /**
     * {@inheritdoc}
     * @suppress PhanTypeMismatchArgumentInternal
     */
    protected function doDelete(string ...$keys): bool {
        if (empty($keys)) return true;
        apcu_delete($keys);
        return count(apcu_exists($keys)) === 0;
    }

    /** {@inheritdoc} */
    protected function doFetch(string ...$keys): Traversable {
        if (empty($keys)) return;
        foreach ($keys as $key) {
            $value = apcu_fetch($key, $success);
            if ($success !== true) $value = null;
            yield $key => $value;
        }
    }

    /** {@inheritdoc} */
    protected function doSave(array $keysAndValues, int $expiry = 0): bool {
        $lifeTime = max(0, $this->expiryToLifetime($expiry));
        return empty(apcu_store($keysAndValues, null, $lifeTime));
    }

}
