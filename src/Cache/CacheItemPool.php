<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Cache\TagInterop\{
    TaggableCacheItemInterface, TaggableCacheItemPoolInterface
};
use DateInterval,
    DateTime,
    Generator,
    JsonSerializable;
use NGSOFT\Traits\{
    LoggerAware, Unserializable
};
use Psr\{
    Cache\CacheException as PSRCacheException, Cache\CacheItemInterface, Cache\CacheItemPoolInterface, Log\LoggerInterface, Log\LogLevel, Log\NullLogger,
    SimpleCache\CacheInterface
};
use Stringable,
    Symfony\Contracts\Cache\ItemInterface,
    Throwable,
    TypeError;
use function get_debug_type;

/**
 * A PSR-16 Cache
 * A PSR-6 Cache Pool
 */
class CacheItemPool implements Pool, Stringable, JsonSerializable {

    use LoggerAware;
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
     * @param CacheDriver $driver The CacheDriver to use
     * @param int $defaultLifetime Default Lifetime in seconds for items that do not define an expirity a value of 0 never expires
     * @param string $namespace Namespace to assign to the driver
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
    public function removeExpired(): bool {
        return $this->driver->removeExpired();
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
            if (count($toRemove) > 0) $r = $this->driver->delete(...$toRemove);
            foreach ($toSave as $expiry => $knv) $r = $this->driver->save($knv, $expiry) && $r;
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
            $keys = array_values(array_unique($keys));
            foreach ($keys as $key) {
                $this->getValidKey($key);
                unset($this->deferred[$key]);
            }
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
            if ($this->deferred) $this->commit();
            foreach ($this->driver->fetch($key) as $value) {
                if ($value instanceof CacheObject) return $this->createItem($key, $value->value, $value->expiry);
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
            if ($this->deferred) $this->commit();
            if (empty($keys)) return;
            $this->doCheckKeys($keys);
            if ($this->deferred) $this->commit();
            $keys = array_values(array_unique($keys));
            foreach ($this->driver->fetch(...$keys) as $key => $value) {
                if ($value instanceof CacheObject) yield $key => $this->createItem($key, $value->value, $value->expiry);
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
        return
                empty($this->getNamespace()) ?
                $this->driver->clear() :
                $this->driver->deleteAll();
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

    /**
     * Assert valid ttl
     *
     * @param mixed $ttl
     * @throws InvalidArgumentException
     */
    protected function doCheckTTL($ttl) {
        try {
            $this->checkType($ttl, 'null', 'int', DateInterval::class);
        } catch (TypeError $error) {
            throw new InvalidArgumentException(sprintf('Invalid $ttl provided. %s', $error->getMessage()));
        }
    }

    /**
     * Logs exception and returns it (modified if needed)
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @param Throwable $exception
     * @param string|null $method
     * @return Throwable
     */
    final protected function handleException(
            Throwable $exception,
            ?string $method = null
    ) {
        $level = LogLevel::ALERT;
        if ($exception instanceof InvalidArgumentException) $level = LogLevel::WARNING;
        $this->log($level, $exception->getMessage(), ['exception' => $exception]);
        if (
                $exception instanceof PSRCacheException and
                $method
        ) {

            $exception = new CacheException(
                    sprintf('Cache Exception thrown in %s::%s', static::class, $method),
                    0,
                    $exception
            );
        }

        return $exception;
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
                TaggableCacheItemPoolInterface::class,
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
