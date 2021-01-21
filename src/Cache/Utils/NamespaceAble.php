<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\Driver,
    UI\Exception\InvalidArgumentException;

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
    private const NAMESPACE_MODIFIER = '%s[%s][%u]';

    /**
     * A Reserved Cache Key that is used to retrieve and save the current namespace version
     * the %s stands for the namespace
     */
    private const NAMESPACE_VERSION_KEY = 'NGSOFT_CACHE_DRIVER_NAMESPACE_VERSION[%s]';

    /** @var string */
    private $namespace;

    /** @param string $namespace */
    public function __construct(
            Driver $driver,
            string $namespace = ''
    ) {
        $this->driver = $this->driver;
        $this->setNamespace($namespace);
    }

    /**
     * Access Currently assigned Driver
     *
     * @return Driver
     */
    public function getDriver(): Driver {
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

    }

    /**
     * Get the currently assigned namespace
     *
     * @return string
     */
    public function getNamespace(): string {

    }

    /**
     * Invalidates current namespace items, increasing the namespace version.
     *
     * @return bool true if the process was successful, false otherwise.
     */
    public function invalidateNamespace(): bool {

    }

}
