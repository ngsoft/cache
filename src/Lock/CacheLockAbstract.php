<?php

declare(strict_types=1);

namespace NGSOFT\Lock;

abstract class CacheLockAbstract extends BaseLockStore
{

    protected const CACHE_KEY_MODIFIER = 'CACHELOCK[%s]';

    protected function getCacheKey(): string
    {
        // prevents filenames to throw cache errors
        return sprintf(self::CACHE_KEY_MODIFIER, hash('MD5', $this->name));
    }

    protected function createEntry(): array
    {

        $data = [
            static::KEY_UNTIL => $this->seconds + $this->timestamp(),
            static::KEY_OWNER => $this->getOwner(),
        ];

        return $data;
    }

}
