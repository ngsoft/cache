<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheDriver, CacheObject, FileSystem, Serializer
};
use Psr\Log\LogLevel,
    Throwable;

class OPHPCache extends FileSystem implements CacheDriver {

    /**
     * File Modification time to add to enable opcache compilation
     * Negative as OPCache compiles files from the past
     */
    private const COMPILE_OFFSET = -86400;

    /**
     * Template to create php file without embeded expiry
     */
    private const TEMPLATE = '<?php return %s;';

    /**
     * Template to create php file with embed expiry that forces a cache miss
     */
    private const TEMPLATE_WITH_EXPIRATION = '<?php return microtime(true) > %u ? null: %s;';

    ////////////////////////////   API   ////////////////////////////

    /**
     * Checks if Zend OPCache is enabled
     * the cache will continue to work if not
     *
     * @staticvar bool $supported
     * @return bool
     */
    public static function isSupported(): bool {
        static $supported;
        if ($supported === null) {
            $supported = false;
            if (
                    function_exists('opcache_invalidate') and
                    filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN) and
                    (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true) or filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN))
            ) $supported = true;
        }
        return $supported;
    }

    /** {@inheritdoc} */
    protected function getExtension(): string {
        return '.php';
    }

    /** {@inheritdoc} */
    protected function doSave(array $keysAndValues, int $expiry = 0): bool {
        $r = true;
        foreach ($keysAndValues as $key => $value) {
            $filename = $this->getFilename($key, $this->getExtension());

            $contents = $this->toPHPCode($value, $expiry);
            if (null !== $contents and $this->write($filename, $contents)) {
                $this->compile($filename);
            } else $r = false;
        }
        return $r;
    }

    /** {@inheritdoc} */
    protected function doClear(): bool {
        $r = true;
        foreach ($this->scanFiles($this->getCacheRoot(), $this->getExtension()) as $file) {
            $this->invalidate($file);
            $r = $this->unlink($file) && $r;
        }
        foreach ($this->scanDirs($this->getCacheRoot()) as $dir) $this->rmdir($dir);
        return $r;
    }

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        if (empty($keys)) return true;
        $r = true;
        foreach ($keys as $key) {
            $filename = $this->getFilename($key, $this->getExtension());
            if (is_file($filename)) {
                $this->invalidate($filename);
                $r = $this->unlink($filename) && $r;
            }
        }
        return $r;
    }

    /** {@inheritdoc} */
    public function purge(): bool {
        $r = true;
        foreach ($this->scanFiles($this->getCacheRoot(), $this->getExtension()) as $file) {
            // embed expiry is useful
            if (!$this->read($file)) {
                $this->invalidate($file);
                $r = $this->unlink($file) && $r;
            }
        }
        return $r;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Reads contents from filename
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @staticvar \Closure $handler
     * @staticvar \Closure $errorHandler
     * @param string $filename
     * @param mixed $value value passed by reference
     * @return bool
     */
    protected function read(string $filename, &$value = null): bool {
        static $handler, $errorHandler;
        // safe include (without context)
        if (!$handler) {
            $handler = static function(string $filename) {
                return include $filename;
            };
            $errorHandler = static function () {

            };
        }
        $value = null;
        if (!is_file($filename)) return false;
        try {
            \set_error_handler($errorHandler);
            if (null === ($result = $handler($filename))) return false;
            $value = $result;
            return true;
        } catch (Throwable $error) {
            $this->log(LogLevel::DEBUG, 'Cache Miss ! A file failed to load.', [
                "driver" => static::class,
                "filename" => $filename,
                "error" => $error
            ]);
            return false;
        } finally { \restore_error_handler(); }
    }

    /**
     * Creates a php template that can be included
     *
     * @param mixed $value
     * @param int|null $expiry
     * @return string|null
     */
    public function toPHPCode($value, int $expiry = null): ?string {
        $expiry = max(0, $expiry ?? 0);
        if ($this->isExpired($expiry)) return null;
        $contents = null;
        if (
                $value instanceof CacheObject and
                $contents = $this->var_exporter($value->toArray())
        ) {
            $contents = sprintf('%s::__set_state(%s)', CacheObject::class, $contents);
        } else $contents = $this->var_exporter($value);
        if (!is_string($contents)) return null;
        if ($expiry > 0) $result = sprintf(self::TEMPLATE_WITH_EXPIRATION, $expiry, $contents);
        else $result = sprintf(self::TEMPLATE, $contents);
        return $result;
    }

    /**
     * Creates a PHP template
     * var_export extended edition
     *
     * @param mixed $data
     * @return string|null PHP Code or null if some data cannot be exported (a cache must load 'all' datas or none)
     */
    protected function var_exporter($data): ?string {
        if (is_array($data)) {
            $toExport = '[';
            foreach ($data as $key => $value) {
                $str = $this->var_exporter($value);
                if ($str === null) return null;
                $toExport .= sprintf('%s=>%s,', var_export($key, true), $str);
            }
            return $toExport . ']';
        } elseif (is_scalar($data) or is_null($data)) return var_export($data, true);
        elseif (is_object($data)) {
            //a lot faster than unserialize
            if (method_exists($data, '__set_state')) return var_export($data, true);
            if (!is_string($serialized = $this->safeSerialize($data))) return null;
            return sprintf('%s::unserialize(%s)', Serializer::class, var_export($serialized, true));
        }
        return null;
    }

    /**
     * Checks if Zend OPCache is supported
     *
     * @return bool
     */
    public function isOPCacheEnabled(): bool {
        return self::isSupported();
    }

    /**
     * Compiles the file using op cache (if supported)
     *
     * @param string $filename
     * @return bool
     */
    protected function compile(string $filename): bool {

        if (
                !$this->isOPCacheEnabled() or
                !is_file($filename)
        ) return false;

        return
                $this->invalidate($filename) &&
                @opcache_compile_file($filename);
    }

    /**
     * Removes filename from op cache
     *
     * @param string $filename
     * @return bool
     */
    protected function invalidate(string $filename): bool {
        if (
                !$this->isOPCacheEnabled() or
                !is_file($filename)
        ) return false;


        return
                @touch($filename, time() + self::COMPILE_OFFSET) &&
                @opcache_invalidate($filename, true);
    }

}
