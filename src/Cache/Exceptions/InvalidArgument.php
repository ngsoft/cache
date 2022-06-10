<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Exceptions;

class InvalidArgument extends CacheError implements \Psr\Cache\InvalidArgumentException, \Psr\SimpleCache\InvalidArgumentException
{

}
