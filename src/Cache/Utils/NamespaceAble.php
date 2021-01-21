<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\{
    Driver, InvalidArgumentException
};

/**
 * Adds the ability to manipulate Namespaces
 *
 */
abstract class NamespaceAble {

    /** @var Driver */
    protected $driver;

    /**
     * Namespaces are used to prevent conflicts between differents applications that can use the same cache keys
     * A clear in a namespace will increment its version, so to remove the entries, use removeExpired()
     * Modifiers stands for Namespace[Key][NamespaceVersion]
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
    private $namespace;

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
     *
     * @return bool true if the process was successful, false otherwise.
     */
    final public function invalidateNamespace(): bool {

        $key = $this->getNamespaceKey();
        $version = $this->getNamespaceVersion() + 1;
        if ($this->driver->set($key, $version)) {
            $this->namespace_version = $version;
            return true;
        }
        return false;
    }

    /**
     * Get the namespaced key (This is the key used in the storage, not the one in the cache item)
     * Must be used when fetching and saving items
     *
     * @param string $key
     * @return string
     */
    final protected function getStorageKey(string $key): string {
        return sprintf(self::NAMESPACE_MODIFIER, $this->getNamespace(), $key, $this->getNamespaceVersion());
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Get the cache key for the namespace
     * @return string
     */
    private function getNamespaceKey(): string {
        return sprintf(self::NAMESPACE_VERSION_KEY, $this->getNamespace());
    }

    /**
     * Get Current Namespace Version
     * @return int
     */
    private function getNamespaceVersion(): int {
        if ($this->namespace_version === null) {
            $key = $this->getNamespaceKey();
            if (is_int($val = $this->driver->get($key))) $this->namespace_version = $val;
            else $this->namespace_version = 1;
        }
        return $this->namespace_version;
    }

}
