<?php

declare(strict_types=1);

namespace NGSOFT\Lock;

use Psr\SimpleCache\CacheInterface;

class SimpleCacheLockStore extends CacheLock
{

    public function __construct(
            protected CacheInterface $cache,
            string $name,
            int|float $seconds,
            string $owner = '',
            bool $autoRelease = true
    )
    {
        parent::__construct($name, $seconds, $owner, $autoRelease);
    }

    /** {@inheritdoc} */
    protected function read(): array|false
    {
        $result = $this->cache->get($this->getCacheKey());
        return is_array($result) ? $result : false;
    }

    protected function write(): bool
    {

    }

    /** {@inheritdoc} */
    public function forceRelease(): void
    {
        $this->cache->delete($this->getCacheKey());
    }

    /** {@inheritdoc} */
    public function release(): bool
    {

        if ($this->isAcquired()) {
            return $this->cache->delete($this->getCacheKey());
        }

        return false;
    }

}
