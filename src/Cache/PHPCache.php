<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\Cache\{
    Drivers\ApcuDriver, Drivers\ArrayDriver, Drivers\ChainDriver, Drivers\PhpDriver, Exceptions\CacheError, Interfaces\CacheDriver
};
use Psr\{
    EventDispatcher\EventDispatcherInterface, Log\LoggerInterface
};

/**
 * A preconfigured cache pool
 * Chains ArrayDriver, ApcuDriver, PhpDriver
 */
final class PHPCache extends CachePool
{

    protected int $defaultLifetime;

    public function __construct(
            string $rootpath = '',
            string $prefix = '',
            int $defaultLifetime = 0,
            ?LoggerInterface $logger = null,
            ?EventDispatcherInterface $eventDispatcher = null
    )
    {

        $this->defaultLifetime = $defaultLifetime;

        $chain = [new ArrayDriver()];

        if (ApcuDriver::isSupported()) {
            $chain[] = new ApcuDriver();
        }

        $chain[] = new PhpDriver($rootpath, $prefix);

        $driver = new ChainDriver($chain);

        parent::__construct($driver, $prefix, $defaultLifetime, $logger, $eventDispatcher);
    }

    protected function setDefaultLifetime(): void
    {
        if ($this->defaultLifetime > 0) {
            $this->driver->setDefaultLifetime($this->defaultLifetime);
        }
    }

    protected function getChain(): array
    {
        return iterator_to_array($this->driver);
    }

    /**
     * Put a driver at the end of the chain
     *
     * @param CacheDriver $driver
     * @return static
     */
    public function appendDriver(CacheDriver $driver): static
    {
        $chain = $this->getChain();

        if (in_array($driver, $chain, true)) {
            throw new CacheError(sprintf('Cannot chain the same instance of "%s" driver twice.', get_class($driver)));
        }

        array_push($chain, $driver);

        $this->driver = new ChainDriver($chain);
        $this->setDefaultLifetime();

        return $this;
    }

    /**
     * Put a driver at the beginning of the chain
     *
     * @param CacheDriver $driver
     * @return static
     */
    public function prependDriver(CacheDriver $driver): static
    {

        $chain = $this->getChain();

        if (in_array($driver, $chain, true)) {
            throw new CacheError(sprintf('Cannot chain the same instance of "%s" driver twice.', get_class($driver)));
        }

        array_unshift($chain, $driver);

        $this->driver = new ChainDriver($chain);
        $this->setDefaultLifetime();
        return $this;
    }

}
