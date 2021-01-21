<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use JsonSerializable;
use NGSOFT\{
    Cache, Cache\Utils\CacheUtils, Events\EventDispatcherAware, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, Log\LoggerAwareInterface
};
use Stringable;

class_exists(CacheItem::class);

/**
 * A PSR-6 CachePool
 */
final class CacheItemPool extends NGSOFT\Cache\Utils\NamespaceAble implements Cache, CacheItemPoolInterface, LoggerAwareInterface, EventDispatcherInterface, Stringable, JsonSerializable {

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

        //$this->setNamespace($namespace);
        //chain cache, doctrine ...
        if (method_exists($driver, 'setDefaultLifetime')) {
            $driver->setDefaultLifetime($this->defaultLifetime);
        }
    }

    /** {@inheritdoc} */
    public function __destruct() {
        $this->commit();
    }

    ////////////////////////////   PSR-6   ////////////////////////////
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
