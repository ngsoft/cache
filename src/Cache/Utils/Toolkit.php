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
     * Convenience function to check if item is expired status against current time
     * @param ?int $expiry
     * @return bool
     */
    protected function isExpired(?int $expiry): bool
    {
        if (null === $expiry) {
            return true;
        }

        return $expiry !== 0 && microtime(true) > $expiry;
    }

    protected function some(callable $callable, iterable $iterable): bool
    {

        foreach ($iterable as $key => $value) {
            if (!$callable($value, $key, $iterable)) {
                continue;
            }
            return true;
        }
        return false;
    }

    protected function every(callable $callable, iterable $iterable): bool
    {
        foreach ($iterable as $key => $value) {

            if (!$callable($value, $key, $iterable)) {
                return false;
            }
        }

        return true;
    }

    public function __debugInfo(): array
    {
        return [];
    }

}
