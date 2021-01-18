<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheDriver, Serializer
};

class PHPFileCache extends FileSystem implements CacheDriver {

    /**
     * File Modification time to add to enable opcache compilation
     * Negative as OPCache compiles files from the past
     */
    private const COMPILE_OFFSET = -86400;

    /**
     * Template to create php file without expirity
     */
    private const TEMPLATE = '<?php return %s;';

    /**
     * Template to create php file with embed expiration
     */
    private const TEMPLATE_WITH_EXPIRATION = '<?php return microtime(true) > %u ? null: %s;';

    /**
     * Extension for value saved as php code
     */
    private const EXTENSION = '.php';

    /**
     * Default Prefix
     */
    private const FILESYSTEM_PREFIX = 'phpcache';

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Creates a PHP template (direct php code to include) and exports data to it
     * var_export extended edition
     *
     * @param mixed $data
     * @return string|null PHP Code or null if some data cannot be exported (a cache must load 'all' datas or none)
     */
    protected function toPHPCode($data): ?string {
        if (is_array($data)) {
            $toExport = '[';
            foreach ($data as $key => $value) {
                $str = $this->export($value);
                if ($str === null) return null;
                $toExport .= sprintf('%s=>%s,', var_export($key, true), $str);
            }

            return $toExport . ']';
        } elseif (is_scalar($data) or is_null($data)) return var_export($data, true);
        elseif (is_object($data)) {
            //a lot faster than unserialize
            if (method_exists($data, '__set_state')) return var_export($data, true);
            // be careful some objects cannot be woke up !
            // var export to prevent \'\'\\' (that sort of things)
            $serialized = $this->safeSerialize($data);
            if (!is_string($serialized)) return null;
            return sprintf('%s::unserialize(%s)', Serializer::class, var_export($serialized, true));
        }
        return null;
    }

    /**
     * Checks if Zend OPCache is supported
     *
     * @staticvar bool $supported
     * @return bool
     */
    public function isOPCacheEnabled(): bool {
        static $supported;
        if ($supported === null) {
            $supported = function_exists('opcache_invalidate') and
                    filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN) and
                    (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true) or filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN));
        }


        return $supported;
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
