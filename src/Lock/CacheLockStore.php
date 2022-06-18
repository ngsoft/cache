<?php

declare(strict_types=1);

namespace NGSOFT\Lock;

use Psr\Cache\CacheItemPoolInterface;

class CacheLockStore extends CacheLock
{

    public function __construct(
            protected CacheItemPoolInterface $cache,
            string $name,
            int|float $seconds,
            string $owner = '',
            bool $autoRelease = true
    )
    {
        parent::__construct($name, $seconds, $owner, $autoRelease);
    }

    protected function read(): array|false
    {

        $item = $this->cache->getItem($this->getCacheKey());
        if ($item->isHit() && is_array($item->get())) {
            return $item->get();
        }

        return false;
    }

    protected function write(): bool
    {

        $expiry = $this->seconds + $this->timestamp();
        $data = [
            self::KEY_UNTIL => $expiry,
            self::KEY_OWNER => $this->getOwner(),
        ];

        $result = $this->cache->save(
                $this->cache
                        ->getItem($this->getCacheKey())
                        ->set($data)->expiresAfter((int) ceil($this->seconds))
        );

        if ($result) {
            $this->until = $expiry;
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function forceRelease(): void
    {
        $this->cache->deleteItem($this->getCacheKey());
    }

    /** {@inheritdoc} */
    public function release(): bool
    {
        if ($this->isAcquired()) {
            return $this->cache->deleteItem($this->getCacheKey());
        }
        return false;
    }

}
