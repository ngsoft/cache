<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Generator,
    JsonSerializable;
use NGSOFT\{
    Cache, Cache\InvalidArgumentException, Cache\Utils\CacheUtils, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemPoolInterface, Log\LoggerAwareInterface, Log\LoggerInterface, SimpleCache\CacheInterface
};
use Stringable,
    Throwable;
use function get_debug_type;

/**
 * Adapter to use any PSR6 Cache pool as a PSR 16 Cache
 * If you use CacheItemPool you have to set the namespace directly using $simplecachepool->getCachePool()->setNamespace();
 */
final class SimpleCachePool implements Cache, CacheInterface, LoggerAwareInterface, Stringable, JsonSerializable {

    use CacheUtils;
    use Unserializable;

    /** @var CacheItemPoolInterface */
    private $pool;

    /** @var int */
    private $defaultLifetime;

    /**
     * @param CacheItemPoolInterface $pool The CacheItemPool
     * @param int $defaultLifetime Default TTL to use when using null as ttl (a value of 0 never expires)
     */
    public function __construct(
            CacheItemPoolInterface $pool,
            int $defaultLifetime = 0
    ) {

        if ($pool instanceof CacheInterface) {
            throw new InvalidArgumentException(sprintf(
                                    'Pool %s is already a PSR16 Cache.',
                                    get_class($pool)
            ));
        }
        // no negative value
        $this->defaultLifetime = max(0, $defaultLifetime);
        $this->pool = $pool;
    }

    ////////////////////////////   LoggerAware   ////////////////////////////

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
        if ($this->pool instanceof LoggerAwareInterface) {
            $this->pool->setLogger($logger);
        }
    }

    ////////////////////////////   Getters   ////////////////////////////

    /**
     * Get Access to the Cache Pool
     * @return CacheItemPoolInterface
     */
    public function getCachePool(): CacheItemPoolInterface {
        return $this->pool;
    }

    /** @return int */
    public function getDefaultLifetime(): int {
        return $this->defaultLifetime;
    }

    ////////////////////////////   API   ////////////////////////////

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function clear() {
        return $this->pool->clear();
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function delete($key) {
        try {
            return $this->pool->deleteItem($key);
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
            return $this->pool->deleteItems($keys);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function get($key, $default = null) {
        try {
            $item = $this->pool->getItem($key);
            return $item->isHit() ?
                    $item->get() :
                    $default;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return Generator
     */
    public function getMultiple($keys, $default = null) {
        try {
            $this->doCheckKeys($keys);
            if (!is_array($keys)) $keys = iterator_to_array($keys);
            if (empty($keys)) return;
            foreach ($this->pool->getItems($keys)as $key => $item) {
                yield $key => $item->isHit() ?
                                $item->get() :
                                $default;
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
            return $this->pool->hasItem($key);
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
            $this->doCheckTTL($ttl);
            $this->doCheckValue($value);
            $ttl = $ttl ?? $this->getDefaultLifetime();
            return $this->pool->save(
                            $this->pool
                                    ->getItem($key)
                                    ->set($value)
                                    ->expiresAfter($ttl === 0 ? null : $ttl)
            );
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
            $this->doCheckTTL($ttl);
            if (!is_array($values)) $values = iterator_to_array($values);
            $ttl = $ttl ?? $this->getDefaultLifetime();

            foreach ($this->pool->getItems(array_keys($values)) as $key => $item) {
                $this->doCheckValue($values[$key]);
                $this->pool->saveDeferred(
                        $item
                                ->set($values[$key])
                                // to prevent cache pool from deleting the item (we use the cache pool default value)
                                ->expiresAfter($ttl === 0 ? null : $ttl)
                );
            }
            return $this->pool->commit();
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
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
            CacheItemPoolInterface::class => get_class($this->pool)
        ];
    }

}
