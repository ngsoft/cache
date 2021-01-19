<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use JsonSerializable;
use NGSOFT\{
    Cache\Utils\CacheUtils, Traits\Unserializable
};
use Psr\Log\LoggerAwareInterface,
    React\Cache\CacheInterface,
    Stringable;

/**
 * React Cache Bridge to use all the drivers available
 *   With that you can use doctrine, laravel, or any PSR cache implementation with React/Promise based code
 *
 */
class ReactCache implements CacheInterface, LoggerAwareInterface, Stringable, JsonSerializable {

    use CacheUtils;
    use Unserializable;

    /**
     * Version Information
     */
    public const VERSION = CacheItemPool::VERSION;

    /** @var CacheDriver */
    protected $driver;

    /** @var int */
    protected $defaultLifetime;

    /**
     * @param CacheDriver $driver The Cache Driver
     * @param int $defaultLifetime TTL to cache entries without expiry values. A value of 0 never expires (or at least until the cache flush it)
     * @param string $namespace the namespace to use
     * @suppress PhanUndeclaredMethod
     */
    public function __construct(
            CacheDriver $driver,
            int $defaultLifetime = 0,
            string $namespace = ''
    ) {


        class_exists(\React\Promise\PromiseInterface::class);

        $this->defaultLifetime = max(0, $defaultLifetime);
        $this->driver = $driver;
        $this->setLogger(new NullLogger());
        $this->setNamespace($namespace);
        //chain cache, doctrine ...
        if (method_exists($driver, 'setDefaultLifetime')) {
            $driver->setDefaultLifetime($this->defaultLifetime);
        }
    }

    ////////////////////////////   Debug Infos   ////////////////////////////

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
                CacheInterface::class
            ],
            'Version' => static::VERSION,
            'Driver Loaded' => $this->driver
        ];
    }

}
