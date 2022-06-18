<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Adapters;

use Doctrine\Common\Cache\CacheProvider;
use NGSOFT\{
    Cache, Cache\Exceptions\CacheError, Cache\Interfaces\CacheDriver, Cache\Utils\PrefixAble, Cache\Utils\Toolkit, Traits\StringableObject, Traits\Unserializable
};
use Psr\Log\{
    LoggerAwareInterface, LoggerInterface
};
use Stringable;

if ( ! interface_exists(CacheProvider::class)) {
    throw new CacheError('doctrine/cache not installed, please run: composer require doctrine/cache');
}

/**
 * @phan-file-suppress PhanUnusedProtectedFinalMethodParameter
 */
final class DoctrineCacheProvider extends CacheProvider implements Cache, LoggerAwareInterface, Stringable
{

    use PrefixAble,
        Unserializable,
        Toolkit,
        StringableObject;

    protected ?LoggerInterface $logger = null;

    /**
     *
     * @param CacheDriver $driver
     * @param string $prefix
     * @param int $defaultLifetime
     */
    public function __construct(
            CacheDriver $driver,
            string $prefix = '',
            int $defaultLifetime = 0
    )
    {
        $this->driver = $driver;

        if ($defaultLifetime > 0) {
            $driver->setDefaultLifetime($defaultLifetime);
        }

        $this->setPrefix($prefix);
    }

    public function getNamespace(): string
    {
        return $this->prefix;
    }

    public function setNamespace($namespace): void
    {
        parent::setNamespace($namespace);

        $this->setPrefix((string) $namespace);
    }

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->driver->setLogger($this->logger = $logger);
    }

    /** {@inheritdoc} */
    public function contains($id): bool
    {
        return $this->driver->has($this->getCacheKey($id));
    }

    /** {@inheritdoc} */
    public function delete($id): bool
    {
        return $this->driver->delete($this->getCacheKey($id));
    }

    /** {@inheritdoc} */
    public function fetch($id): mixed
    {
        return $this->driver->get($this->getCacheKey($id));
    }

    /** {@inheritdoc} */
    public function save($id, $data, $lifeTime = 0): bool
    {
        // defaultLifetime
        if ($lifeTime === 0) {
            $lifeTime = null;
        }

        return $this->driver->set($this->getCacheKey($id), $data, $lifeTime);
    }

    /** {@inheritdoc} */
    public function deleteAll(): bool
    {
        return $this->invalidate();
    }

    /** {@inheritdoc} */
    public function flushAll(): bool
    {
        $this->setPrefix($this->prefix);
        return $this->driver->clear();
    }

    /** {@inheritdoc} */
    public function deleteMultiple(array $keys): bool
    {

        $result = true;
        foreach ($keys as $key) {
            if ( ! $this->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function fetchMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $value = $this->fetch($key);
            if (is_null($value)) {
                continue;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function saveMultiple(array $keysAndValues, $lifetime = 0): bool
    {

        $result = true;

        foreach ($keysAndValues as $key => $value) {

            if ( ! $this->save($key, $value, $lifetime)) {
                $result = false;
            }
        }

        return $result;
    }

    // all is disabled after this

    public function getStats()
    {
        return null;
    }

    protected function doContains($id)
    {
        return false;
    }

    protected function doDelete($id)
    {
        return false;
    }

    protected function doFetch($id)
    {
        return null;
    }

    protected function doFlush()
    {
        return false;
    }

    protected function doGetStats()
    {
        return null;
    }

    protected function doSave($id, $data, $lifeTime = 0)
    {
        return false;
    }

}
