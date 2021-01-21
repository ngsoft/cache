<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

interface NamespaceAble {

    /**
     * Change the namespace for the current instance
     *   A namespace is a modifier assigned to the key
     *
     * @param string $namespace The prefix to use
     * @throws InvalidArgumentException if the namespace is invalid: '{}()/\@:' are found.
     * @return void
     */
    public function setNamespace(string $namespace): void;

    /**
     * Get the currently assigned namespace
     *
     * @return string
     */
    public function getNamespace(): string;

    /**
     * Invalidates current namespace items, increasing the namespace version.
     *
     * @return bool true if the process was successful, false otherwise.
     */
    public function invalidateNamespace(): bool;
}
