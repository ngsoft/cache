<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Utils;

use ErrorException,
    Throwable;

/**
 * Static Serializer to use with the cache entries (OPCache Mainly)
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
     * Recursively walk the array to check if there are values that cannot be exported
     *
     * @param array $array
     * @return bool
     */
    public static function canVarExport(array $array): bool {

        foreach ($array as $v) {
            if (is_scalar($v) or is_null($v)) continue;
            if (is_object($v) and method_exists($v, '__set_state')) continue; //better use serialize for included objects
            if (is_array($v) and self::canVarExport($v)) continue;
            return false;
        }
        return true;
    }

    /**
     * Type hint var_export
     *
     * @param mixed $input
     * @return string|null null if cannot be used for the cache needs
     */
    public static function var_export($input): ?string {

        if (is_scalar($input) or is_null($input)) return var_export($input, true);
        if (is_object($input) and method_exists($input, '__set_state')) return var_export($input, true);
        if (is_array($input) and self::canVarExport($input)) return var_export($input, true);
        return null;
    }

    /**
     * Prevents Thowable inside classes __sleep or __serialize methods to interrupt operations
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
     * Prevents Thowable inside classes __wakeup or __unserialize methods to interrupt operations
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
            // we already checked if a false has been serialized: so that's unserialize that failed
            // (we must already be inside the catch block but we don't know for sure)
            return $result === false ? null : $result;
        } catch (Throwable $ex) { return null; } finally { \restore_error_handler(); }
    }

}
