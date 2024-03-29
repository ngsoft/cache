<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use NGSOFT\Cache\Exceptions\CacheError;
use Psr\{
    Cache\CacheException, Cache\InvalidArgumentException, Log\LoggerAwareTrait, Log\LogLevel
};
use Throwable;

trait ExceptionLogger
{

    use LoggerAwareTrait;

    /**
     * Logs exception and returns it (modified if needed)
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @param Throwable $exception
     * @param string|null $method
     * @return Throwable
     */
    final protected function handleException(
            Throwable $exception,
            ?string $method = null
    )
    {
        $level = LogLevel::ERROR;

        if ($exception instanceof InvalidArgumentException) { $level = LogLevel::WARNING; }

        if ($exception instanceof CacheException === false) {
            $exception = new CacheError('An error has occured.', previous: $exception);
        }
        if ($method) {
            $this->logger?->log($level, sprintf('Cache Exception thrown in %s::%s', static::class, $method), [
                'exception' => $exception
            ]);
        }



        return $exception;
    }

}
