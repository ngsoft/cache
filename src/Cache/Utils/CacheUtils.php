<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use DateInterval,
    ErrorException;
use NGSOFT\{
    Cache\CacheException, Cache\CacheItem, Cache\InvalidArgumentException, Traits\LoggerAware, Traits\UnionType
};
use Psr\Log\{
    LoggerInterface, LogLevel
};
use Throwable,
    TypeError;
use function get_debug_type;

//preload classes for better performances (loading there as a almost all classes uses that trait)
class_exists(InvalidArgumentException::class);
class_exists(CacheException::class);

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

        if (
                $exception instanceof CacheException and
                $method
        ) {
            $this->log($level, sprintf('Cache Exception thrown in %s::%s', static::class, $method), [
                'exception' => $exception
            ]);
        }

        return $exception;
    }

    ////////////////////////////   Helpers   ////////////////////////////

    /**
     * Convenient Function used to convert php errors, warning, ... as ErrorException
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @staticvar Closure $handler
     * @return void
     */
    protected function setErrorHandler(): void {
        static $handler;
        if (!$handler) {
            $handler = static function ($type, $msg, $file, $line) {
                throw new ErrorException($msg, 0, $type, $file, $line);
            };
        }
        set_error_handler($handler);
    }

    /**
     * @param mixed $name
     * @return string
     */
    protected function getValidKey($name): string {
        if (!is_string($name)) {
            throw new InvalidArgumentException(sprintf(
                                    'Cache key must be string, "%s" given.',
                                    get_debug_type($name)
            ));
        }
        if ('' === $name) {
            throw new InvalidArgumentException('Cache key length must be greater than zero.');
        }
        if (false !== strpbrk($name, '{}()/\@:')) {
            throw new InvalidArgumentException(sprintf(
                                    'Cache key "%s" contains reserved characters "%s".',
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
        foreach ($keys as $key) $this->getValidKey($key);
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
     * Convenience function to check if item is expired status against current time
     * @param int|null $expire
     * @return bool
     */
    protected function isExpired(int $expire = null): bool {
        $expire = $expire ?? 0;
        return
                $expire != 0 and
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
                $expiry != 0 ?
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
     * @return CacheItem
     */
    protected function createItem(string $key, $value = null): CacheItem {
        static $create, $item;
        if (!$item) {
            $item = new CacheItem('CacheItem');
            $create = static function (string $key, $value, LoggerInterface $logger = null) use ($item): CacheItem {
                $c = clone $item;
                $c->key = $key;
                $c->value = $value;
                // to log exceptions
                $logger and $c->setLogger($logger);
                return $c;
            };
            $create = $create->bindTo(null, CacheItem::class);
        }
        return $create($key, $value, $this->logger);
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
