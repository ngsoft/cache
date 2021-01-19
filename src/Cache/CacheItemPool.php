<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Cache\TagInterop\TaggableCacheItemInterface,
    DateInterval,
    DateTime,
    Generator,
    JsonSerializable,
    NGSOFT\Traits\Unserializable;
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, Log\LoggerInterface, Log\NullLogger, SimpleCache\CacheInterface
};
use Stringable,
    Symfony\Contracts\Cache\ItemInterface,
    Throwable;
use function get_debug_type;

/**
 * A PSR-16 Cache
 * A PSR-6 Cache Pool
 * If you don't use tags this the one you will be using to increase performances (as the taggable one needs to load the tags each time an item is saved/deleted)
 */
class CacheItemPool implements Pool, Stringable, JsonSerializable {

    use CacheUtils;
    use Unserializable;

    /**
     * Version Information
     */
    public const VERSION = '1.0';

    /** @var CacheDriver */
    protected $driver;

    /** @var int */
    protected $defaultLifetime;

    /** @var CacheItem[] */
    protected $deferred = [];

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
        //chain cache, doctrine ...
        if (method_exists($driver, 'setDefaultLifetime')) {
            $driver->setDefaultLifetime($this->defaultLifetime);
        }
    }

    /** {@inheritdoc} */
    public function __destruct() {
        $this->commit();
    }

    /**
     * Access Currently assigned Driver
     *
     * @return CacheDriver
     */
    public function getDriver(): CacheDriver {
        return $this->driver;
    }

    ////////////////////////////   Pool   ////////////////////////////

    /** {@inheritdoc} */
    public function deleteAll(): bool {

        return $this->driver->deleteAll();
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

    ////////////////////////////   PSR-6   ////////////////////////////

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function commit() {
        try {
            if (empty($this->deferred)) return true;

            $r = true;
            $items = $this->deferred;
            $this->deferred = $toSave = $toRemove = [];
            //group items by expiries
            foreach ($items as $key => $item) {
                if (!$item->isHit()) {
                    $toRemove[] = $key;
                    continue;
                }
                $expiry = $item->getExpiration() ?? $this->getExpiryRealValue();
                $toSave[$expiry] = $toSave[$expiry] ?? [];
                //a CacheObject makes it easier to retrieve item expiry
                $toSave[$expiry] [$key] = new CacheObject($key, $item->get(), $expiry);
            }
            foreach ($toSave as $expiry => $knv) $r = $this->driver->save($knv, $expiry) && $r;
            if (count($toRemove) > 0) $r = $this->driver->delete(...$toRemove) && $r;
            return $r;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function deleteItem($key) {
        try {
            return $this->deleteItems([$key]);
        } catch (Throwable $error) {
            //keeps a consistent stack trace
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function deleteItems(array $keys) {
        if (empty($keys)) return true;
        try {
            $this->doCheckKeys($keys);
            $keys = array_values(array_unique($keys));
            foreach ($keys as $key) {
                $this->getValidKey($key);
                unset($this->deferred[$key]);
            }
            $this->commit();
            return $this->driver->delete(...$keys);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return CacheItemInterface|CacheItem
     */
    public function getItem($key): CacheItemInterface {
        try {
            $key = $this->getValidKey($key);
            if (isset($this->deferred[$key])) {
                return clone $this->deferred[$key];
            }
            foreach ($this->driver->fetch($key) as $value) {
                if ($value instanceof CacheObject) return $this->createItem($key, $value->value, $value->expiry, $value->tags);
            }
            return $this->createItem($key);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return Generator|CacheItem[]
     */
    public function getItems(array $keys = []) {
        try {
            if (empty($keys)) return;
            $this->doCheckKeys($keys);
            $keys = array_values(array_unique($keys));
            if (count($this->deferred) > 0) {
                $items = [];
                $missing = array_combine($keys, $keys);
                foreach ($keys as $key) {
                    if (isset($this->deferred[$key])) {
                        $items[$key] = clone $this->deferred[$key];
                        unset($missing[$key]);
                    }
                }
                $missing = array_values($missing);
                foreach ($this->driver->fetch(...$missing) as $key => $value) {
                    if ($value instanceof CacheObject) $items[$key] = $this->createItem($key, $value->value, $value->expiry, $value->tags);
                    else $items[$key] = $this->createItem($key);
                }
                foreach ($keys as $key) {
                    yield $key => $items[$key];
                }
                return;
            }

            foreach ($this->driver->fetch(...$keys) as $key => $value) {
                if ($value instanceof CacheObject) yield $key => $this->createItem($key, $value->value, $value->expiry, $value->tags);
                else yield $key => $this->createItem($key);
            }
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function hasItem($key): bool {
        try {
            $key = $this->getValidKey($key);
            if (isset($this->deferred[$key])) $this->commit();
            return $this->driver->contains($key);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function save(CacheItemInterface $item) {
        try {
            return $this->saveDeferred($item) and $this->commit();
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item) {
        if (!($item instanceof CacheItem)) {
            throw $this->handleException(
                            new InvalidArgumentException(sprintf(
                                            'Cache items are not transferable between pools. %s requested, %s given',
                                            CacheItem::class,
                                            get_class($item)
                                    )),
                            __FUNCTION__
            );
        }
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    ////////////////////////////   PSR-16   ////////////////////////////

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function delete($key) {
        try {
            $key = $this->getValidKey($key);
            return $this->deleteItems([$key]);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function deleteMultiple($keys) {
        try {
            $this->doCheckKeys($keys);
            if (!is_array($keys)) $keys = iterator_to_array($keys);
            return $this->deleteItems($keys);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function get($key, $default = null) {

        try {
            $item = $this->getItem($key);
            return
                    $item->isHit() ?
                    $item->get() :
                    $default;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return \Generator
     */
    public function getMultiple($keys, $default = null) {
        if (empty($keys)) return;
        try {
            $this->doCheckKeys($keys);
            if (!is_array($keys)) $keys = iterator_to_array($keys);
            foreach ($this->getItems($keys)as $key => $item) {
                yield $key => $item->isHit() ? $item->get() : $default;
            }
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function has($key) {
        try {
            return $this->hasItem($key);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function set($key, $value, $ttl = null) {
        try {
            $key = $this->getValidKey($key);
            return $this->setMultiple([$key => $value], $ttl);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function setMultiple($values, $ttl = null) {
        try {
            if (!is_iterable($values)) {
                throw new InvalidArgumentException(sprintf('Invalid $values iterable expected, %s given.', get_debug_type($values)));
            }
            if (empty($values)) return true;
            $expire = $this->getExpiration($ttl);
            foreach ($values as $key => $value) {
                $this->doCheckValue($value);
                $key = $this->getValidKey($key);
                $this->saveDeferred($this->createItem($key, $value, $expire));
            }
            return $this->commit();
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    ////////////////////////////   Both   ////////////////////////////

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function clear() {
        $this->deferred = [];
        return $this->driver->clear();
    }

    ////////////////////////////   LoggerAware   ////////////////////////////

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger) {
        $this->driver->setLogger($logger);
        $this->logger = $logger;
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
        else $expire = time() + $ttl;
        return $expire;
    }

    /** {@inheritdoc} */
    public function __debugInfo() {
        return $this->jsonSerialize();
    }

    /** {@inheritdoc} */
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {

        return [
            static::class => [
                CacheItemPoolInterface::class,
                CacheInterface::class,
            ],
            CacheItem::class => [
                CacheItemInterface::class,
                TaggableCacheItemInterface::class,
                ItemInterface::class,
            ],
            'Version' => static::VERSION,
            'Driver Loaded' => $this->driver
        ];
    }

}
