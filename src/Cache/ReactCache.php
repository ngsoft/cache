<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\{
    Cache, Cache\Utils\CacheUtils, Cache\Utils\NamespaceAble, Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface
};
use React\Cache\CacheInterface,
    Throwable;
use function React\Promise\resolve;

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
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->driver->setLogger($logger);
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function clear() {
        return resolve(parent::clear());
    }

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

    public function delete($key) {
        try {

        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function set($key, $value, $ttl = null) {
        try {

        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

}
