<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Generator;
use NGSOFT\{
    Cache\CacheUtils, Cache\InvalidArgumentException, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemPoolInterface, Log\LoggerAwareInterface, Log\LoggerInterface, SimpleCache\CacheInterface
};
use Throwable;
use function get_debug_type;

/**
 * Adapter to use PSR6 Cache pool as a PSR 16 Cache
 * Reverse adapter exists as Drivers\SimpleCache
 */
class SimpleCachePool implements CacheInterface, LoggerAwareInterface {

    use CacheUtils;
    use Unserializable;

    /** @var CacheItemPoolInterface */
    private $pool;

    /** @var int */
    private $defaultLifetime;

    /**
     * @param CacheItemPoolInterface $pool The CacheItemPool
     * @param int $defaultLifetime Default TTL to use when using null as ttl
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
    public function getPool(): CacheItemPoolInterface {
        return $this->pool;
    }

    /** @return int|null */
    public function getDefaultLifetime(): ?int {
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
            $item = $this->pool
                    ->getItem($key)
                    ->set($value)
                    ->expiresAfter($ttl);
            return $this->pool->save($item);
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
            /** @var \Psr\Cache\CacheItemInterface $item */
            foreach ($this->pool->getItems(array_keys($values)) as $key => $item) {
                $this->doCheckValue($values[$key]);
                $item
                        ->set($values[$key])
                        ->expiresAfter($ttl);
                $this->pool->saveDeferred($item);
            }
            return $this->pool->commit();
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

}
