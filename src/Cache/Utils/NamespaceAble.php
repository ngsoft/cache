<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\{
    Driver, InvalidArgumentException
};

/**
 * Adds the ability to manipulate Namespaces
 */
abstract class NamespaceAble {

    /**
     * Namespaces are used to prevent conflicts between differents applications that can use the same cache keys
     *
     * Modifiers stands for Namespace_Key_NamespaceVersion
     */
    private const NAMESPACE_MODIFIER = '%s_%s_%u';

    /**
     * A Reserved Cache Key that is used to retrieve and save the current namespace version
     * the %s stands for the namespace
     */
    private const NAMESPACE_VERSION_KEY = '%s_NAMESPACE_VERSION';

    /**
     * Used to hold current namespace version
     * Changing the version invalidates cache items without removing them physically
     *
     * @var int|null
     */
    private $namespace_version = null;

    /** @var string */
    private $namespace = '';

    /** @var Driver */
    protected $driver;

    /** @param string $namespace */
    public function __construct(
            Driver $driver,
            string $namespace = ''
    ) {
        $this->driver = $driver;
        $this->setNamespace($namespace);
    }

    ////////////////////////////   API   ////////////////////////////

    /**
     * Access Currently assigned Driver
     *
     * @return Driver
     */
    final public function getDriver(): Driver {
        return $this->driver;
    }

    /**
     * Removes expired item entries if driver supports it
     *
     *
     * @return bool true if operation was successful, false if not supported or error
     */
    public function purge(): bool {
        return $this->driver->purge();
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool   true on success, false otherwise
     */
    public function clear() {
        $this->namespace_version = null;
        return $this->getDriver()->clear();
    }

    /**
     * Change the namespace for the current instance
     *   A namespace is a modifier assigned to the key
     *
     * @param string $namespace The prefix to use
     * @throws InvalidArgumentException if the namespace is invalid: '{}()/\@:' are found.
     * @return void
     */
    public function setNamespace(string $namespace): void {
        if (!empty($namespace) and (false !== strpbrk($namespace, '{}()/\@:'))) {
            throw new InvalidArgumentException(sprintf('Cache namespace "%s" contains reserved characters "%s".', $namespace, '{}()/\@:'));
        }
        $this->namespace = $namespace;
    }

    /**
     * Get the currently assigned namespace
     *
     * @return string
     */
    final public function getNamespace(): string {
        return $this->namespace;
    }

    /**
     * Invalidates current namespace items, increasing the namespace version.
     * If no namespace is set it will do nothing (and return false)
     *
     * @return bool true if the process was successful, false otherwise.
     */
    final public function invalidateNamespace(): bool {
        if (!empty($this->namespace)) {
            $version = $this->getNamespaceVersion() + 1;
            if ($this->driver->set($this->getNamespaceKey(), $version)) {
                $this->namespace_version = $version;
                return true;
            }
        }
        return false;
    }

    ////////////////////////////   Helpers   ////////////////////////////

    /**
     * Get the namespaced key (This is the key used in the storage, not the one in the cache item)
     * Must be used when fetching and saving items
     *
     * @param string $key
     * @return string
     */
    final protected function getStorageKey(string $key): string {
        if (empty($this->namespace)) return $key;
        return sprintf(self::NAMESPACE_MODIFIER, $this->namespace, $key, $this->getNamespaceVersion());
    }

    /**
     * Get the cache key for the namespace
     * @return string
     */
    private function getNamespaceKey(): string {
        return sprintf(self::NAMESPACE_VERSION_KEY, $this->namespace);
    }

    /**
     * Get Current Namespace Version
     * @return int
     */
    private function getNamespaceVersion(): int {
        if ($this->namespace_version === null) {
            if (!is_int($val = $this->driver->get($this->getNamespaceKey()))) {
                $this->namespace_version = 1;
            } else $this->namespace_version = $val;
        }
        return $this->namespace_version;
    }

}
