<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use DateInterval,
    DateTime,
    JsonSerializable;
use NGSOFT\{
    Cache\Utils\CacheUtils, Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface, NullLogger
};
use React\Cache\CacheInterface,
    Stringable,
    Throwable;
use function React\Promise\{
    reject, resolve
};

/**
 * React Cache Bridge to use all the drivers available,
 * To use it you must require react/cache using composer
 *
 *   With that you can use doctrine, laravel, or any PSR cache implementation with React/Promise based code
 *   That implementation rejects if values are incorrect types, cache keys or ttl are PSR invalid
 */
final class ReactCache implements CacheInterface, LoggerAwareInterface, Stringable, JsonSerializable {

    use CacheUtils;
    use Unserializable;

    /**
     * Version Information
     */
    public const VERSION = CacheItemPool::VERSION;

    /** @var CacheDriver */
    protected $driver;

    /** @var int */
    protected $defaultLifetime;

    /**
     * @param CacheDriver $driver The Cache Driver
     * @param int $defaultLifetime TTL to cache entries without expiry values. A value of 0 never expires (or at least until the cache flush it)
     * @param string $namespace the namespace to use
     * @suppress PhanUndeclaredMethod
     */
    public function __construct(
            CacheDriver $driver,
            int $defaultLifetime = 0,
            string $namespace = ''
    ) {
        $this->defaultLifetime = max(0, $defaultLifetime);
        $this->driver = $driver;
        $this->setLogger(new NullLogger());
        $this->setNamespace($namespace);
        // chain, doctrine ...
        if (method_exists($driver, 'setDefaultLifetime')) {
            $driver->setDefaultLifetime($this->defaultLifetime);
        }
    }

    /**
     * Access Currently assigned Driver
     *
     * @return CacheDriver
     */
    public function getDriver(): CacheDriver {
        return $this->driver;
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function clear() {
        return resolve($this->driver->clear());
    }

    /** {@inheritdoc} */
    public function has($key) {
        try {
            $key = $this->getValidKey($key);
            return resolve($this->driver->contains($key));
        } catch (Throwable $error) {
            return reject($this->handleException($error, __FUNCTION__));
        }
    }

    /** {@inheritdoc} */
    public function get($key, $default = null) {
        try {
            $key = $this->getValidKey($key);
            //we know it will not be rejected
            return $this
                            ->getMultiple([$key], $default)
                            ->then(fn($r) => $r[$key]);
        } catch (Throwable $error) {
            return reject($this->handleException($error, __FUNCTION__));
        }
    }

    /** {@inheritdoc} */
    public function set($key, $value, $ttl = null) {
        try {
            $key = $this->getValidKey($key);
            return $this->setMultiple([$key => $value], $ttl);
        } catch (Throwable $error) {
            return reject($this->handleException($error, __FUNCTION__));
        }
    }

    /** {@inheritdoc} */
    public function delete($key) {
        return $this->deleteMultiple([$key]);
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys) {
        try {
            $this->doCheckKeys($keys);
            return resolve($this->driver->delete(... array_values($keys)));
        } catch (Throwable $error) {
            return reject($this->handleException($error, __FUNCTION__));
        }
    }

    /** {@inheritdoc} */
    public function getMultiple(array $keys, $default = null) {
        try {
            $this->doCheckKeys($keys);
            $result = [];
            foreach ($this->driver->fetch(...array_values($keys)) as $key => $value) {
                if ($value instanceof CacheObject) $result[$key] = $value->value === null ? $default : $value->value;
                else $result[$key] = $value === null ? $default : $value;
            }
            return resolve($result);
        } catch (Throwable $error) {
            return reject($this->handleException($error, __FUNCTION__));
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(array $values, $ttl = null) {
        try {
            if (is_float($ttl)) $ttl = (int) ceil($ttl); // ceil as round can return 0
            $this->doCheckTTL($ttl);
            $this->doCheckKeys(array_keys($values));
            $expiry = $this->getExpiration($ttl);
            // keep compatibility with cache pool
            $toSave = [];
            foreach ($values as $key => $value) {
                $this->doCheckValue($value);
                $toSave[$key] = new CacheObject($key, $value, $expiry);
            }
            return resolve($this->driver->save($toSave, $expiry));
        } catch (Throwable $error) {
            return reject($this->handleException($error, __FUNCTION__));
        }
    }

    ////////////////////////////   LoggerAware   ////////////////////////////

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger) {
        $this->driver->setLogger($logger);
        $this->logger = $logger;
    }

    ////////////////////////////   Pool Compatibility   ////////////////////////////

    /** {@inheritdoc} */
    public function invalidate(): bool {
        return $this->driver->invalidateAll();
    }

    /** {@inheritdoc} */
    public function purge(): bool {
        return $this->driver->purge();
    }

    /** {@inheritdoc} */
    public function getNamespace(): string {
        return $this->driver->getNamespace();
    }

    /** {@inheritdoc} */
    public function setNamespace(string $namespace): void {
        try {
            $this->driver->setNamespace($namespace);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Converts null Item Expiry into an expiration timestamp
     *
     * @param int|null $expiry
     * @return int
     */
    protected function getExpiryRealValue(int $expiry = null): int {
        if ($expiry === null) $expiry = $this->defaultLifetime > 0 ? (time() + $this->defaultLifetime) : 0;
        return $expiry;
    }

    /**
     * Convert lifetime to expiration time
     *
     * @param null|int|DateInterval $ttl
     * @return int
     */
    protected function getExpiration($ttl): int {
        $this->doCheckTTL($ttl);
        if ($ttl === null) $expire = $this->getExpiryRealValue();
        elseif ($ttl instanceof DateInterval) $expire = (new DateTime())->add($ttl)->getTimestamp();
        elseif ($ttl == 0) $expire = $ttl;
        else $expire = time() + $ttl;
        return $expire;
    }

    ////////////////////////////   Debug Infos   ////////////////////////////

    /** {@inheritdoc} */
    public function __debugInfo() {
        return [
            'Informations' => $this->__toString()
        ];
    }

    /** {@inheritdoc} */
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {

        return [
            'Cache' => static::class,
            'Version' => static::VERSION,
            'Implements' => array_values(class_implements($this)),
            'Driver' => $this->driver
        ];
    }

}
