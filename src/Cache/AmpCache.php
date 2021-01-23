<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Amp\{
    Cache\Cache, Cache\CacheException, Promise, Success
};
use NGSOFT\{
    Cache\Utils\CacheUtils, Cache\Utils\NamespaceAble, Traits\Unserializable
};

/**
 * This is a bridge between my drivers and amphp/cache
 *   - amphp/cache must be installed to use this
 */
class AmpCache extends NamespaceAble implements Cache {

    use CacheUtils;
    use Unserializable;

    /**
     * @param Driver $driver The Cache Driver
     * @param string $namespace the namespace to use
     * @suppress PhanUndeclaredMethod
     */
    public function __construct(
            Driver $driver,
            string $namespace = ''
    ) {

        if (method_exists($driver, 'setDefaultLifetime')) {

            $driver->setDefaultLifetime(0);
        }

        parent::__construct($driver, $namespace);
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function delete(string $key): Promise {
        $nkey = $this->getStorageKey($key);
        if ($exists = $this->driver->has($nkey)) {
            if (!$this->driver->delete($nkey)) {
                throw new CacheException('Cannot delete ' . $key);
            }
        }
        return new Success($exists);
    }

    /** {@inheritdoc} */
    public function get(string $key): Promise {
        $value = $this->driver->get($this->getStorageKey($key));
        if (
                !is_string($value) and
                !is_null($value)
        ) {
            throw new CacheException('Cannot get ' . $key);
        }
        return new Success($value);
    }

    /** {@inheritdoc} */
    public function set(string $key, string $value, int $ttl = null): Promise {
        if ($ttl === null) $expiry = 0;
        elseif (0 > $ttl) {
            throw new \Error("Invalid cache TTL ({$ttl}; integer >= 0 or null required");
        } else $expiry = time() + $ttl; // time() + 0 expires in 1 second, would have been better to replace 0 with a default value
        $nkey = $this->getStorageKey($key);

        if (!$this->driver->set($nkey, $value, $expiry)) {
            throw new CacheException('Cannot set ' . $key);
        }

        return new Success();
    }

    /**
     * Invalidates current namespace items, increasing the namespace version.
     * If no namespace is set it will do nothing (and return false)
     *
     * @return Promise<bool> true if the process was successful, false otherwise (if there was no namespace in the first place or an error occured).
     */
    public function invalidate(): Promise {
        return new Success($this->invalidateNamespace());
    }

}
