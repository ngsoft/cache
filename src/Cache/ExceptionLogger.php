<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Psr\{
    Cache\CacheException, Log\LoggerAwareTrait, Log\LogLevel
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
        $level = LogLevel::ALERT;
        if ($exception instanceof InvalidArgument) $level = LogLevel::WARNING;

        if ($exception instanceof CacheException && $method) {
            $this->logger?->log($level, sprintf('Cache Exception thrown in %s::%s', static::class, $method), [
                'exception' => $exception
            ]);
        }

        return $exception;
    }

}
