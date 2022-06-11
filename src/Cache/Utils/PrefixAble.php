<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\{
    Interfaces\CacheDriver, InvalidArgument, Item
};

class PrefixAble
{

    protected const PREFIX_KEY_MODIFIER = '%s[%s][%u]';
    protected const PREFIX_VERSION_KEY = '%s[VERSION]';

    protected string $prefix = '';
    protected int $version = -1;

    public function __construct(
            protected CacheDriver $driver,
            string $prefix = ''
    )
    {

        $this->setPrefix($prefix);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
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
        if (false !== strpbrk($namespace, Item::RESERVED_CHAR_KEY)) {
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
    public function invalidatePrefix(): bool
    {

        if ($this->prefix === '') {
            return false;
        }
        $this->version = $this->driver->increment($this->getPrefixVersionKey());
        return true;
    }

    final protected function getPrefixedKey(string $key): string
    {
        Item::validateKey($key);
        return
                $this->prefix === '' ?
                $key :
                sprintf(self::PREFIX_KEY_MODIFIER, $this->prefix, $key, $this->getPrefixVersion());
    }

    final protected function getPrefixVersion(): int
    {
        $this->version = -1 === $this->version || $this->driver->get($this->getPrefixVersionKey(), 0);
        return $this->version;
    }

    final protected function getPrefixVersionKey(): string
    {
        return sprintf(static::PREFIX_VERSION_KEY, $this->prefix);
    }

}
