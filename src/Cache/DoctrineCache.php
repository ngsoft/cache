<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Doctrine\Common\Cache\{
    Cache as DoctrineCacheInterface, ClearableCache, FlushableCache, MultiOperationCache
};
use NGSOFT\{
    Cache, Cache\Utils\CacheUtils, Cache\Utils\NamespaceAble, Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface
};

/**
 * This is a bridge between my drivers and Doctrine Cache
 *   - doctrine/cache must be installed to use that feature
 */
final class DoctrineCache extends NamespaceAble implements Cache, DoctrineCacheInterface, FlushableCache, ClearableCache, MultiOperationCache, LoggerAwareInterface {

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
    public function contains($id) {
        $this->checkType($id, 'string');
        return $this->driver->has($this->getStorageKey($id));
    }

    /** {@inheritdoc} */
    public function delete($id) {
        $this->checkType($id, 'string');
        return $this->driver->delete($this->getStorageKey($id));
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys) {
        $this->doCheckKeys($keys);
        $keysToDelete = array_map(fn($k) => $this->getStorageKey($k), array_values($keys));
        return $this->driver->deleteMultiple($keysToDelete);
    }

    /** {@inheritdoc} */
    public function fetch($id) {
        $this->checkType($id, 'string');
        $value = $this->driver->get($this->getStorageKey($id));
        return $value === null ? false : $value;
    }

    /** {@inheritdoc} */
    public function fetchMultiple(array $keys) {
        $this->doCheckKeys($keys);
        $map = array_combine(array_map(fn($k) => $this->getStorageKey($k), array_values($keys)), array_values($keys));
        $result = [];
        foreach ($this->driver->getMultiple(array_keys($map))as $nkey => $value) {
            if ($value !== null) $result[$map[$nkey]] = $value;
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function save($id, $data, $lifeTime = 0) {
        $this->checkType($id, 'string');
        $this->checkType($lifeTime, 'int');
        $expiry = $lifeTime !== 0 ? time() + $lifeTime : 0;
        return $this->driver->set($this->getStorageKey($id), $data, $expiry);
    }

    /** {@inheritdoc} */
    public function saveMultiple(array $keysAndValues, $lifetime = 0) {
        $this->checkType($lifetime, 'int');
        $expiry = $lifetime !== 0 ? time() + $lifetime : 0;
        $toSave = array_combine(array_map(fn($k) => $this->getStorageKey($k), array_keys($keysAndValues)), array_values($keysAndValues));
        return $this->driver->setMultiple($toSave, $expiry);
    }

    /** {@inheritdoc} */
    public function deleteAll() {
        return $this->invalidateNamespace();
    }

    /** {@inheritdoc} */
    public function flushAll() {
        return $this->driver->clear();
    }

    /** {@inheritdoc} */
    public function getStats() {
        return [
            'Cache' => static::class,
            'Version' => static::VERSION,
            'Implements' => array_values(class_implements($this)),
            'Driver' => (string) $this->getDriver()
        ];
    }

}
