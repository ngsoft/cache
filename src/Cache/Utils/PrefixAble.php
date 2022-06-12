<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\{
    Exceptions\InvalidArgument, Interfaces\CacheDriver, Item
};

trait PrefixAble
{

    protected string $prefix = '';
    protected int $version = -1;

    public function __construct(
            protected CacheDriver $driver,
            string $prefix = ''
    )
    {

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
        if (false !== strpbrk($prefix, Item::RESERVED_CHAR_KEY)) {
            throw new InvalidArgument(sprintf('Cache prefix "%s" contains reserved characters "%s".', $prefix, Item::RESERVED_CHAR_KEY));
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
        Item::validateKey($key);
        return
                $this->prefix === '' ?
                $key :
                sprintf('%s[%s][%u]', $this->prefix, $key, $this->getPrefixVersion());
    }

    final protected function getPrefixVersion(): int
    {
        $this->version = -1 === $this->version ? $this->driver->get($this->getPrefixVersionKey(), 0) : $this->version;
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
