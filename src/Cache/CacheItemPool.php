<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Generator,
    JsonSerializable;
use NGSOFT\{
    Cache, Cache\Utils\CacheUtils, Cache\Utils\NamespaceAble, Events\EventDispatcherAware, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, Log\LoggerAwareInterface
};
use Stringable,
    Throwable;

class_exists(CacheItem::class);

/**
 * A PSR-6 CachePool
 */
final class CacheItemPool extends NamespaceAble implements Cache, CacheItemPoolInterface, LoggerAwareInterface, EventDispatcherInterface, Stringable, JsonSerializable {

    use CacheUtils;
    use Unserializable;
    use EventDispatcherAware;

    /** @var int */
    private $defaultLifetime;

    /** @var CacheItem[] */
    private $deferred = [];

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

    /** {@inheritdoc} */
    public function __destruct() {
        $this->commit();
    }

    ////////////////////////////   PSR-6   ////////////////////////////

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function clear(): bool {
        return $this->getDriver()->clear();
    }

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
                $expiry = $item->getExpiry() ?? $this->getExpiryRealValue();
                $toSave[$expiry] = $toSave[$expiry] ?? [];
                $toSave[$expiry] [$key] = $item->get();
            }
            foreach ($toSave as $expiry => $values) {
                $r = $this->driver->setMultiple($knv, $expiry) && $r;
            }
            if (count($toRemove) > 0) $r = $this->driver->deleteMultiple($toRemove) && $r;
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
