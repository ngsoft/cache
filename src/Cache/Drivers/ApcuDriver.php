<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

class ApcuDriver extends BaseCacheDriver
{

    public static function isSupported(): bool
    {
        static $result;
    }

}
