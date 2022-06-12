<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use ErrorException;

trait Toolkit
{

    /**
     * Convenient Function used to convert php errors, warning, ... as ErrorException
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @staticvar Closure $handler
     * @return void
     */
    protected function setErrorHandler(): void
    {
        static $handler;
        if (!$handler) {
            $handler = static function ($type, $msg, $file, $line) {
                throw new ErrorException($msg, 0, $type, $file, $line);
            };
        }
        set_error_handler($handler);
    }

    /**
     * Convenience function to convert expiry into TTL
     * A TTL/expiry of 0 never expires
     *
     *
     * @param int $expiry
     * @return int the ttl a negative ttl is already expired
     */
    protected function expiryToLifetime(int $expiry): int
    {
        return
                $expiry !== 0 ?
                $expiry - time() :
                0;
    }

    /**
     * Convenience function to check if item is expired status against current time
     * @param ?int $expiry
     * @return bool
     */
    protected function isExpired(?int $expiry): bool
    {
        if (null === $expiry) {
            return false;
        }

        return $expiry !== 0 && microtime(true) > $expiry;
    }

}
