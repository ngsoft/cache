<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

class CacheError extends \RuntimeException implements \Psr\Cache\CacheException, \Psr\SimpleCache\CacheException
{

}
