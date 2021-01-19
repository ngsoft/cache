<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use Cache\TagInterop\TaggableCacheItemPoolInterface,
    DateInterval;
use NGSOFT\{
    Cache\CacheDriver, Cache\CacheException, Cache\CacheItem, Cache\CacheObject, Cache\InvalidArgumentException, Traits\LoggerAware, Traits\UnionType
};
use Psr\{
    Cache\CacheException as PSR6CacheException, Log\LoggerAwareInterface, Log\LogLevel, SimpleCache\CacheException as PSR16CacheException
};
use Throwable,
    TypeError;
use function get_debug_type;

//preload classes for better performances (loading there as a almost all classes uses that trait)
interface_exists(LoggerAwareInterface::class);
interface_exists(CacheDriver::class);

class_exists(InvalidArgumentException::class);
class_exists(CacheException::class);
class_exists(CacheItem::class);
class_exists(LogLevel::class);
class_exists(CacheObject::class);

/**
 * Reusable Methods for Cache Implementation
 *
 * @phan-file-suppress PhanAccessPropertyProtected
 */
trait CacheUtils {

    use UnionType;
    use LoggerAware;





    ////////////////////////////   LoggerAware   ////////////////////////////

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
                ($exception instanceof PSR6CacheException or
                $exception instanceof PSR16CacheException) and
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

    ////////////////////////////   Helpers   ////////////////////////////

    /**
     * @param mixed $name
     * @return string
     */
    protected function getValidKey($name): string {
        return static::getValidName($name);
    }

    /**
     * @param mixed $name
     * @return string
     */
    protected function getValidTag($name): string {
        return static::getValidName($name, 'tag');
    }

    /**
     * Get the valid representation for the given name, also validate
     * @param mixed $name
     * @param string $type tag or key
     * @return string
     * @throws InvalidArgumentException if given name is invalid
     */
    protected static function getValidName($name, string $type = 'key'): string {
        if (
                $type == 'tag' and
                !is_string($name) and
                !(is_object($name) and method_exists($name, '__toString'))
        ) {
            throw new InvalidArgumentException(sprintf(
                                    'Cache tag must be string or object that implements __toString(), "%s" given.',
                                    is_object($name) ? get_class($name) : get_debug_type($name)
            ));
        } elseif (
                $type == 'key' and
                !is_string($name)
        ) {
            throw new InvalidArgumentException(sprintf(
                                    'Cache %s must be string, "%s" given.',
                                    $type,
                                    get_debug_type($name)
            ));
        }

        $name = (string) $name;

        if ('' === $name) {
            throw new InvalidArgumentException(sprintf('Cache %s length must be greater than zero.', $type));
        }
        if (false !== strpbrk($name, '{}()/\@:')) {
            throw new InvalidArgumentException(sprintf(
                                    'Cache %s "%s" contains reserved characters "%s".',
                                    $type,
                                    $name,
                                    '{}()/\@:'
            ));
        }

        return $name;
    }

    /**
     * Check iterables keys
     *
     * @param mixed $keys
     * @throws InvalidArgumentException
     */
    protected function doCheckKeys($keys) {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException(sprintf('Invalid argument $keys, iterable expected, %s given.', get_debug_type($keys)));
        }
        foreach ($keys as $key) $this->getValidName($key);
    }

    /**
     * Check if value is valid (PSR-16)
     *
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    protected function doCheckValue($value) {
        try {
            $this->checkType($value, 'scalar', 'null', 'array', 'object');
        } catch (TypeError $error) {
            throw new InvalidArgumentException(sprintf('Invalid value provided. %s', $error->getMessage()));
        }
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
     * Check value against specified types
     *
     * @param mixed $value
     * @param string ...$types
     * @throws InvalidArgumentException
     */
    protected function doCheck($value, string ...$types) {

        try {

            $this->checkType($value, ...$types);
        } catch (TypeError $error) {
            throw new InvalidArgumentException($error->getMessage());
        }
    }

    /**
     * Convenience function to check if item is expired status against current time
     * @param int|null $expire
     * @return bool
     */
    protected function isExpired(int $expire = null): bool {

        $expire = $expire ?? 0;
        return
                $expire > 0 and
                microtime(true) > $expire;
    }

    /**
     * Convenience function to convert expiry into TTL
     * A TTL/expiry of 0 never expires
     *
     *
     * @param int $expiry
     * @return int the ttl a negative ttl is already expired
     */
    protected function expiryToLifetime(int $expiry): int {
        return
                $expiry > 0 ?
                $expiry - time() :
                0;
    }

    /**
     * Creates a CacheItem
     *
     * @staticvar \Closure $create
     * @staticvar CacheItem $item
     * @param string $key
     * @param mixed $value
     * @param int|null $expire
     * @param string[] $tags
     * @param bool|null $tagAware Determines if pool is tag aware
     * @return CacheItem
     */
    protected function createItem(string $key, $value = null, int $expire = null, array $tags = [], bool $tagAware = null): CacheItem {
        static $create, $item;
        if (!$item) {
            $item = new CacheItem('CacheItem');
            $create = static function (string $key, $value, int $expire = null, array $tags = [], bool $tagAware = false) use ($item): CacheItem {
                $c = clone $item;
                $c->key = $key;
                $c->tags = $tags;
                $c->expiry = $expire;
                $c->tagAware = $tagAware;
                // checks valid data
                $c->set($value);
                return $c;
            };

            $create = $create->bindTo(null, CacheItem::class);
        }
        //auto determine if tag aware
        if ($tagAware === null) $tagAware = $this instanceof TaggableCacheItemPoolInterface;

        return $create($key, $value, $expire, $tags, $tagAware);
    }

    ////////////////////////////   Debug Informations   ////////////////////////////

    /** {@inheritdoc} */
    public function __toString() {
        return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
    }

    /** {@inheritdoc} */
    public function jsonSerialize() {
        return static::class;
    }

    /** {@inheritdoc} */
    public function __debugInfo() {
        return [];
    }

}
