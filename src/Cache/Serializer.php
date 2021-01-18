<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use ErrorException,
    Throwable;

/**
 * Static Serializer to use with the cache entries
 */
final class Serializer {

    /**
     * Convenience Function used to convert php errors, warning, ... as ErrorException
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @staticvar \Closure $handler
     */
    private static function setErrorHandler() {
        static $handler;
        if (!$handler) {
            $handler = static function ($type, $msg, $file, $line) {
                throw new ErrorException($msg, 0, $type, $file, $line);
            };
        }
        \set_error_handler($handler);
    }

    /**
     * Prevents Thowable inside classes __sleep or __serialize methods to interrupt operarations
     *
     * @param mixed $input
     * @return string|null
     */
    public static function serialize($input): ?string {
        if ($input === null) return null;
        try {
            static::setErrorHandler();
            return \serialize($input);
        } catch (Throwable $ex) { return null; } finally { \restore_error_handler(); }
    }

    /**
     * Prevents Thowable inside classes __wakeup or __unserialize methods to interrupt operarations
     * Also the warning for wrong input
     *
     * @param string $input
     * @return mixed|null
     */
    public static function unserialize($input) {
        if (!is_string($input)) return null;
        // prevents cache miss
        switch ($input) {
            case 'b:1;':
                return true;
            case 'b:0;':
                return false;
        }
        try {
            self::setErrorHandler();
            $result = \unserialize($input);
            return $result === false ? null : $result;
        } catch (Throwable $ex) { return null; } finally { \restore_error_handler(); }
    }

}
