<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\{
    Exceptions\InvalidArgument, Interfaces\CacheDriver, CacheItem
};

trait PrefixAble
{

    protected CacheDriver $driver;
    protected string $prefix = '';
    protected int $version = -1;

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

    /**
     * Change cache prefix
     *
     * @param string $prefix
     * @return void
     * @throws InvalidArgument
     */
    public function setPrefix(string $prefix): void
    {
        if (false !== strpbrk($prefix, CacheItem::RESERVED_CHAR_KEY)) {
            throw new InvalidArgument(sprintf('Cache prefix "%s" contains reserved characters "%s".', $prefix, CacheItem::RESERVED_CHAR_KEY));
        }
        $this->prefix = $prefix;
        $this->version = -1;
    }

    public function getDriver(): CacheDriver
    {
        return $this->driver;
    }

    /**
     * Increase prefix version, invalidating all prefixed entries
     *
     * @return bool
     */
    public function invalidate(): bool
    {

        if ($this->prefix === '') {
            return false;
        }
        $this->version = $this->driver->increment($this->getPrefixVersionKey());
        return true;
    }

    final protected function getCacheKey(string $key): string
    {
        CacheItem::validateKey($key);
        return
                $this->prefix === '' ?
                $key :
                sprintf('%s[%s][%u]', $this->prefix, $key, $this->getPrefixVersion());
    }

    final protected function getPrefixVersion(): int
    {
        $this->version = -1 === $this->version ? $this->driver->get($this->getPrefixVersionKey(), fn() => 0) : $this->version;
        return $this->version;
    }

    final protected function getPrefixVersionKey(): string
    {
        return sprintf('%s[VERSION]', $this->prefix);
    }

    public function __debugInfo(): array
    {
        return [
            'prefix' => $this->prefix,
            'version' => $this->getPrefixVersion(),
            CacheDriver::class => $this->driver,
        ];
    }

}
