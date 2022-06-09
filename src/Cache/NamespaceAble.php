<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\Traits\StringableObject,
    Psr\Log\LoggerAwareInterface,
    Stringable;

abstract class NamespaceAble implements Stringable, LoggerAwareInterface
{

    use ExceptionLogger,
        StringableObject;

    /**
     * Namespaces are used to prevent conflicts between differents applications that can use the same cache keys
     *
     * Modifiers stands for Namespace[Key][Version]
     */
    protected const NAMESPACE_MODIFIER = '%s[%s][%u]';

    /**
     * A Reserved Cache Key that is used to retrieve and save the current namespace version
     * the %s stands for the namespace
     */
    protected const NAMESPACE_VERSION_KEY = '%s[NAMESPACE_VERSION]';

    /**
     * Used to hold current namespace version
     * Changing the version invalidates cache items without removing them physically
     *
     * @var int|null
     */
    protected ?int $namespaceVersion = null;
    protected string $namespace = '';
    protected TaggedCacheDriver|CacheDriver $driver;

    public function __construct(
            TaggedCacheDriver|CacheDriver $driver,
            string $namespace = ''
    )
    {
        $this->driver = $driver;
        $this->setNamespace($namespace);
    }

    /**
     * Used when adapter use clear()
     * @return bool
     */
    final protected function clearNamespace(): bool
    {
        if (empty($this->namespace)) {
            return true;
        }
        $this->namespaceVersion = null;
        return $this->driver->delete($this->getNamespaceVersionKey());
    }

    /**
     * Change the current namespace
     *
     * @param string $namespace
     * @return void
     * @throws InvalidArgument
     */
    public function setNamespace(string $namespace): void
    {
        if (false !== strpbrk($namespace, Item::RESERVED_CHAR_KEY)) {
            throw new InvalidArgument(sprintf('Cache namespace "%s" contains reserved characters "%s".', $namespace, Item::RESERVED_CHAR_KEY));
        }
        $this->clearNamespace();
        $this->namespace = $namespace;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getDriver(): TaggedCacheDriver
    {
        return $this->driver;
    }

    /**
     * Invalidates current namespace items, increasing the namespace version.
     * If no namespace is set it will do nothing (and return false)
     *
     * @return bool
     */
    final public function invalidateNamespace(): bool
    {
        if (!empty($namespace = $this->namespace)) {
            $version = $this->getNamespaceVersion() + 1;
            if ($this->driver->set($this->getNamespaceVersionKey(), $version)) {
                $this->namespaceVersion = $version;
                return true;
            }
        }
        return false;
    }

    /**
     * @return int
     */
    final protected function getNamespaceVersion(): int
    {

        if (null === $this->namespaceVersion) {
            $version = $this->driver->getRaw($this->getNamespaceVersionKey());
            $this->namespaceVersion = is_int($version) ? $version : 1;
        }
        return $this->namespaceVersion;
    }

    /**
     * @return string
     */
    final protected function getNamespaceVersionKey(): string
    {
        return sprintf(self::NAMESPACE_VERSION_KEY, $this->namespace);
    }

    /**
     * Get namespaced cache key
     *
     * @param mixed $key
     * @return string
     */
    final protected function getCacheKey(mixed $key): string
    {
        Item::validateKey($key);
        if (!empty($this->namespace)) {
            $key = sprintf(self::NAMESPACE_MODIFIER, $this->namespace, $key, $this->getNamespaceVersion());
        }
        return $key;
    }

}
