<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Generator,
    JsonSerializable;
use NGSOFT\{
    Cache, Cache\Events\CacheHit, Cache\Events\CacheMiss, Cache\Events\KeyDeleted, Cache\Events\KeySaved, Cache\Utils\CacheUtils, Cache\Utils\NamespaceAble,
    Events\EventDispatcherAware, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, Log\LoggerAwareInterface, Log\LoggerInterface
};
use Stringable,
    Throwable;

// preload (for better performances)
class_exists(CacheItem::class);

/**
 * A PSR-6 Cache Pool that Supports:
 *  - Namespaces (+Namespace invalidation)
 *  - PSR-14 Events (if you provide a PSR-14 Event Dispatcher using $pool->setEventDispatcher() eg: symfony/event-dispatcher)
 *  - Drivers that supports the most useful providers (Doctrine, Symfony(via PSR-6 proxy(if using ChainDriver)), Illuminate, any PSR-6/16 implementation)
 *
 *  - Don't supports tags at the moment as it adds more computing to the save and delete methods (I tried (even created a shared list system for that),
 *    you can use 'cache/taggable-cache'<https://github.com/php-cache/taggable-cache> with that class if you want tag support)
 */
class CacheItemPool extends NamespaceAble implements Cache, CacheItemPoolInterface, LoggerAwareInterface, EventDispatcherInterface, Stringable, JsonSerializable {

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

    ////////////////////////////   LoggerAware   ////////////////////////////

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->driver->setLogger($logger);
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
            $this->deferred = $toSave = $toRemove = $assoc = [];
            //group items by expiries
            foreach ($items as $key => $item) {
                // expired? null value?
                if (!$item->isHit()) {
                    $toRemove[] = $key;
                    continue;
                }
                $expiry = $item->getExpiry() ?? $this->getExpiryRealValue();
                $toSave[$expiry] = $toSave[$expiry] ?? [];
                // namespaced key to give the driver
                $nKey = $this->getStorageKey($key);
                $toSave[$expiry] [$nKey] = $item->get();
                // keep a link between real/user key (for Events)
                $assoc[$nKey] = $key;
            }
            foreach ($toSave as $expiry => $values) {
                //psr-14 only if we have a dispatcher
                //no need to loop for nothing
                if (
                        ($result = $this->getDriver()->setMultiple($values, $expiry)) and
                        $this->getEventDispatcher()
                ) {
                    foreach ($values as $nKey => $value) $this->dispatch(new KeySaved($assoc[$nKey], $value));
                }
                // keeps the false (if any)
                $r = $result && $r;
            }
            if (count($toRemove) > 0) $r = $this->deleteItems($toRemove) && $r;
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
            // to not copy/paste code...
            return $this->deleteItems([$key]);
        } catch (Throwable $error) {
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
            $keys = array_values(array_unique($keys));
            $keysToRemove = [];
            foreach ($keys as $key) {
                $this->getValidKey($key);
                unset($this->deferred[$key]);
                //namespaced key to pass to driver
                $keysToRemove[] = $this->getStorageKey($key);
            }
            //same as previously
            if (
                    ( $result = $this->getDriver()->deleteMultiple($keysToRemove))
                    and $this->getEventDispatcher()
            ) {
                foreach ($keys as $key) $this->dispatch(new KeyDeleted($key));
            }
            return $result;
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
            // driver singles are there for providers that don't support multi-operations (and other cache implementations (Amp/Doctrine))
            // and to use the driver directly if needed
            foreach ($this->getItems([$key]) as $item) {
                return $item;
            }
            //phan won't stop about it
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
            // 'yield' in a function: function returns: \Generator
            // even if we do that
            if (empty($keys)) return;
            $keys = array_values(array_unique($keys));
            $missing = $items = [];
            // if item already in deferred we don't need to ask the driver for an old value
            foreach ($keys as $key) {
                $this->getValidKey($key);
                if (isset($this->deferred[$key])) {
                    //we get a copy (to not save the wrong datas, if that item is saved it will overwrite the old one (it's why save() commits))
                    $items[$key] = clone $this->deferred[$key];
                } else $missing[$this->getStorageKey($key)] = $key; // associate real/user key
            }
            if (count($missing) > 0) {
                foreach ($this->getDriver()->getMultiple(array_keys($missing)) as $nKey => $value) {
                    $key = $missing[$nKey];
                    // we don't need to know the expiry as it will be overwritten on save
                    // and user has no way to know
                    $items[$key] = $this->createItem($key, $value);
                }
            }
            // now we issue the items
            foreach ($items as $key => $item) {
                //psr-14 support
                if ($this->getEventDispatcher()) {
                    if ($item->isHit()) {
                        // to not modify original data
                        $c = clone $item;
                        $this->dispatch(new CacheHit($key, $c->get()));
                    } else $this->dispatch(new CacheMiss($key));
                }
                yield $key => $item;
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
            $this->getValidKey($key);
            if (isset($this->deferred[$key])) $this->commit();
            return $this->getDriver()->has($this->getStorageKey($key));
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
        // as we have no way to know expiry on third party items, we have to do that
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
    private function getExpiryRealValue(int $expiry = null): int {
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
            'Driver' => $this->getDriver()
        ];
    }

}
