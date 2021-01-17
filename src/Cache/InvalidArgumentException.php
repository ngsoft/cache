<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\Cache\InvalidArgumentException, \Psr\SimpleCache\InvalidArgumentException {

}
