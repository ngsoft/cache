<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Exceptions;

class InvalidArgument extends \InvalidArgumentException implements \Psr\Cache\InvalidArgumentException, \Psr\SimpleCache\InvalidArgumentException
{

}
