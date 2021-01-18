<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

class APCuDriver extends \NGSOFT\Cache\BaseDriver implements \NGSOFT\Cache\CacheDriver {

    public function __construct() {

        if (
                !(function_exists('apcu_fetch') and
                filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)) or
                (PHP_SAPI === 'cli' and (int) ini_get('apc.enable_cli') !== 1)
        ) throw new CacheException('APCu not enabled.');
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

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        if (empty($keys)) return true;
        apcu_delete($keys);
        return count(apcu_exists($keys)) === 0;
    }

    protected function doFetch(string ...$keys): \Traversable {
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
