<?php

declare(strict_types=1);

namespace NGSOFT\Facades;

use Closure;
use NGSOFT\{
    Cache\Exceptions\InvalidArgument, Cache\Interfaces\CacheDriver, Cache\Interfaces\TaggableCacheItem, Container\ContainerInterface, Container\ServiceProvider,
    Container\SimpleServiceProvider, Lock\LockStore
};
use Psr\Cache\CacheItemInterface;

class FileCache extends Facade
{

    protected static function getFacadeAccessor(): string
    {

        return static::getAlias();
    }

    protected static function getServiceProvider(): ServiceProvider
    {
        $accessor = static::getFacadeAccessor();
        return new SimpleServiceProvider(
                $accessor,
                function (ContainerInterface $container) use ($accessor) {

                    if ($container->has($accessor)) {
                        return;
                    }

                    $rootpath = $prefix = '';

                    $defaultLifetime = 0;

                    if ($container->has('Config')) {
                        $prefix = $container->get('Config')['cache.prefix'] ?? $prefix;
                        $rootpath = $container->get('Config')['cache.rootpath'] ?? $rootpath;
                        $defaultLifetime = $container->get('Config')['cache.seconds'] ?? $defaultLifetime;
                    }

                    $chain = [new ArrayDriver()];
                    if (ApcuDriver::isSupported()) {
                        $chain[] = new ApcuDriver();
                    }

                    $chain[] = new FileDriver($rootpath, $prefix);

                    $container->set($accessor, $container->make(CachePool::class, [
                                'driver' => new ChainDriver($chain),
                                'prefix' => $prefix . 'fs',
                                'defaultLifetime' => $defaultLifetime
                    ]));
                }
        );
    }

    /**
     * Invalidates cached items using tags.
     *
     * When implemented on a PSR-6 pool, invalidation should not apply
     * to deferred items. Instead, they should be committed as usual.
     * This allows replacing old tagged values by new ones without
     * race conditions.
     *
     * @param string[]|string $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws InvalidArgument When $tags is not valid
     */
    public static function invalidateTags(array|string $tags): bool
    {
        return static::getFacadeRoot()->invalidateTags($tags);
    }

    /**
     * Removes expired item entries if supported
     *
     * @return void
     */
    public static function purge(): void
    {
        static::getFacadeRoot()->purge();
    }

    /**
     * Fetches a value from the pool or computes it if not found.
     *
     * @param string $key
     * @param mixed|Closure $default if set the item will be saved with that value
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getFacadeRoot()->get($key, $default);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function increment(string $key, int $value = 1): int
    {
        return static::getFacadeRoot()->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function decrement(string $key, int $value = 1): int
    {
        return static::getFacadeRoot()->decrement($key, $value);
    }

    /**
     * Adds data if it doesn't already exists
     *
     * @param string $key
     * @param mixed|Closure $value
     * @return bool True if the data have been added, false otherwise
     */
    public static function add(string $key, mixed $value): bool
    {
        return static::getFacadeRoot()->add($key, $value);
    }

    /** {@inheritdoc} */
    public static function clear(): bool
    {
        return static::getFacadeRoot()->clear();
    }

    /** {@inheritdoc} */
    public static function commit(): bool
    {
        return static::getFacadeRoot()->commit();
    }

    /** {@inheritdoc} */
    public static function deleteItem(string $key): bool
    {
        return static::getFacadeRoot()->deleteItem($key);
    }

    /** {@inheritdoc} */
    public static function deleteItems(array $keys): bool
    {
        return static::getFacadeRoot()->deleteItems($keys);
    }

    /** {@inheritdoc} */
    public static function getItem(string $key): TaggableCacheItem
    {
        return static::getFacadeRoot()->getItem($key);
    }

    /** {@inheritdoc} */
    public static function getItems(array $keys = []): iterable
    {
        return static::getFacadeRoot()->getItems($keys);
    }

    /** {@inheritdoc} */
    public static function hasItem(string $key): bool
    {
        return static::getFacadeRoot()->hasItem($key);
    }

    /** {@inheritdoc} */
    public static function save(CacheItemInterface $item): bool
    {
        return static::getFacadeRoot()->save($item);
    }

    /** {@inheritdoc} */
    public static function saveDeferred(CacheItemInterface $item): bool
    {
        return static::getFacadeRoot()->saveDeferred($item);
    }

    /** {@inheritdoc} */
    public static function lock(string $name, int|float $seconds = 0, string $owner = ''): LockStore
    {
        return static::getFacadeRoot()->lock($name, $seconds, $owner);
    }

    /**
     * Change cache prefix
     *
     * @param string $prefix
     * @return void
     * @throws InvalidArgument
     */
    public static function setPrefix(string $prefix): void
    {
        static::getFacadeRoot()->setPrefix($prefix);
    }

    /**
     * Access Cache driver directly
     */
    public static function getDriver(): CacheDriver
    {
        return static::getFacadeRoot()->getDriver();
    }

    /**
     * Increase prefix version, invalidating all prefixed entries
     *
     * @return bool
     */
    public static function invalidate(): bool
    {
        return static::getFacadeRoot()->invalidate();
    }

}
