<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\{
    Cache, Cache\Utils\CacheUtils, Cache\Utils\NamespaceAble, Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface
};
use React\{
    Cache\CacheInterface, Promise\PromiseInterface
};
use Throwable;
use function React\Promise\resolve;

interface_exists(PromiseInterface::class);

class ReactCache extends NamespaceAble implements Cache, CacheInterface, LoggerAwareInterface {

    use CacheUtils;
    use Unserializable;

    /** @var int */
    private $defaultLifetime;

    /**
     * @param Driver $driver The Cache Driver
     * @param int $defaultLifetime TTL to cache entries without expiry values. A value of 0 never expires (or at least until the cache flush it)
     * @param string $namespace the namespace to use
     * @suppress PhanUndeclaredMethod
     */
    public function __construct(
            Driver $driver,
            int $defaultLifetime = 0,
            string $namespace = ''
    ) {
        $this->defaultLifetime = max(0, $defaultLifetime);
        //chain cache, doctrine ...
        if (method_exists($driver, 'setDefaultLifetime')) {
            $driver->setDefaultLifetime($this->defaultLifetime);
        }

        parent::__construct($driver, $namespace);
    }

    ////////////////////////////   LoggerAware   ////////////////////////////

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger): void {
        $this->logger = $logger;
        $this->driver->setLogger($logger);
    }

    ////////////////////////////   API   ////////////////////////////

    /**
     * {@inheritdoc}
     * @suppress PhanParamSignatureMismatch
     * @return PromiseInterface<bool>
     */
    public function clear() {
        return resolve($this->clearNamespace());
    }

    /** {@inheritdoc} */
    public function has($key) {
        try {
            $this->getValidKey($key);
            return resolve($this->getDriver()->has($this->getStorageKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function get($key, $default = null) {
        try {
            $this->getValidKey($key);
            return resolve($this->getDriver()->get($this->getStorageKey($key)) ?? $default);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function delete($key) {
        try {
            $this->getValidKey($key);
            return resolve($this->getDriver()->delete($this->getStorageKey($key)));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function set($key, $value, $ttl = null) {
        try {
            $this->getValidKey($key);
            $this->doCheckValue($value);
            if (is_float($ttl)) $ttl = (int) ceil($ttl);
            $this->doCheckTTL($ttl);
            $ttl = $ttl ?? $this->defaultLifetime;
            $expiry = $ttl != 0 ? time() + $ttl : 0;
            if ($this->isExpired($expiry)) return $this->delete($key);
            return resolve($this->getDriver()->set($this->getStorageKey($key), $value, $expiry));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys) {
        try {
            $this->doCheckKeys($keys);
            $keysToRemove = array_map(fn($k) => $this->getStorageKey($k), array_values($keys));
            return resolve($this->getDriver()->deleteMultiple($keysToRemove));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys, $default = null) {
        try {
            $this->doCheckKeys($keys);
            $keys = array_values(array_unique($keys));
            $keysToGet = array_combine(array_map(fn($k) => $this->getStorageKey($k), $keys), $keys);
            $result = [];
            foreach ($this->getDriver()->getMultiple(array_keys($keysToGet)) as $nkey => $value) {
                $result[$keysToGet[$nkey]] = $value ?? $default;
            }
            return resolve($result);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, $ttl = null) {
        try {
            if (is_float($ttl)) $ttl = (int) ceil($ttl);
            $this->doCheckTTL($ttl);
            $ttl = $ttl ?? $this->defaultLifetime;
            $expiry = $ttl != 0 ? time() + $ttl : 0;
            if ($this->isExpired($expiry)) return $this->deleteMultiple(array_keys($values));
            $toSave = [];
            foreach ($values as $key => $value) {
                $this->doCheckValue($value);
                $this->getValidKey($key);
                $toSave[$this->getStorageKey($key)] = $value;
            }
            return resolve($this->getDriver()->setMultiple($toSave, $expiry));
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

}
