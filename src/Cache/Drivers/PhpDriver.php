<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use FilesystemIterator,
    Generator,
    InvalidArgumentException;
use NGSOFT\Cache\{
    CacheEntry, CacheError
};
use RecursiveDirectoryIterator,
    Symfony\Component\VarExporter\VarExporter,
    Throwable;
use function mb_strlen,
             str_ends_with,
             str_starts_with;

class PhpDriver extends BaseCacheDriver
{

    protected const STORAGE_PREFIX = '@';
    protected const CHMOD_DIR = 0777;
    protected const CHMOD_FILE = 0666;
    protected const HASH_CHARCODES = '0123456789abcdef';
    protected const COMPILE_OFFSET = -86400;
    protected const STRING_SIZE_LIMIT = 512000;
    protected const TEMPLATE = "<?php\n\nreturn \\NGSOFT\\Cache\\CacheEntry::create(\n    key: '%s',\n    expiry: %d,\n    value: %s\n);";
    protected const TEMPLATE_TXT = 'file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . %s)';

    protected array $tmpFiles = [];
    protected ?string $tmpFile = null;

    public static function opCacheSupported(): bool
    {
        static $result;

        if ($result === null) {
            $result = false;
            if (
                    function_exists('opcache_invalidate') &&
                    filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN) &&
                    (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true) || filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN))
            ) {
                $result = true;
            }
        }
        return $result;
    }

    public static function onWindows(): bool
    {
        static $result;
        $result = $result ?? PHP_OS_FAMILY === 'Windows';
        return $result;
    }

    public function __construct(
            protected string $root = '',
            protected string $prefix = ''
    )
    {

        try {
            $this->setErrorHandler();

            $this->prefix = !empty($prefix) ? $prefix : strtolower(substr(static::class, 1 + strrpos(static::class, '\\')));
            $this->root = $this->normalizePath(!empty($root) ? $root : sys_get_temp_dir());

            $this->mkdir($this->root);

            if (!is_dir($this->root) || !is_writable($this->root)) {
                throw new CacheError(sprintf('Cannot use "%s" as root directory as it is not writable.', $root));
            }

            $this->root .= DIRECTORY_SEPARATOR . static::STORAGE_PREFIX . $this->prefix;
            $this->root = $this->normalizePath($this->root);

            if (self::onWindows() && mb_strlen($this->root) > 200) {
                throw new InvalidArgumentException(sprintf('Cache directory "%s" too long for windows filesystem.', $this->root));
            }

            if (!$this->mkdir($this->root)) {
                throw new CacheError(sprintf('Cannot create storage directory "%s".', $this->root));
            }
        } finally { restore_error_handler(); }
    }

    public function __destruct()
    {

        foreach (array_pop($this->tmpFiles) as $file) {
            $this->unlink($file);
        }
    }

    final protected function normalizePath(string $file): string
    {

        return preg_replace('#[\\\/]+#', DIRECTORY_SEPARATOR, $file);
    }

    final protected function mkdir(string $dir): bool
    {
        return is_dir($dir) || mkdir($dir, self::CHMOD_DIR, true);
    }

    final protected function rmdir(string $dir): bool
    {
        return is_dir($dir) && $this->isEmptyDir($dir) && rmdir($dir);
    }

    final protected function unlink(string $file): bool
    {
        return !is_file($file) || unlink($file);
    }

    final protected function chmod(string $file): bool
    {
        return is_file($file) && chmod($file, self::CHMOD_FILE);
    }

    final protected function getHashedChar(): Generator
    {
        for ($i = 0; $i < 16; $i++) {
            yield self::HASH_CHARCODES[$i];
        }
    }

    final protected function getDirs(string $root): Generator
    {
        if (is_dir($root)) {
            foreach ($this->getHashedChar() as $char1) {
                foreach ($this->getHashedChar() as $char2) {
                    $file = $char1 . $char2;

                    if (is_dir($root . DIRECTORY_SEPARATOR . $file)) {
                        yield $file => $root . DIRECTORY_SEPARATOR . $file;
                    }
                }
            }
        }
    }

    final protected function isEmptyDir(string $dir): bool
    {
        $iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        return iterator_count($iterator) === 0;
    }

    final protected function getFiles(string $root, string $extension = ''): Generator
    {

        $extensions = explode('|', $extension);
        $extensions = array_map(fn($ex) => str_starts_with($ex, '.') ? $ex : ".$ex", $extensions);

        foreach ($this->getDirs($root) as $dir) {
            foreach (scandir($dir, SCANDIR_SORT_NONE) ?: [] as $file) {
                if ($file === '.' || $file === '..' || strpos($file, '.') !== 32) {
                    continue;
                }

                foreach ($extensions as $ext) {
                    if (str_ends_with($file, $ext)) {
                        yield $file => $dir . DIRECTORY_SEPARATOR . $file;
                    }
                }
            }
        }
    }

    final protected function compile(string $file): bool
    {
        if (!static::opCacheSupported() || !is_file($file) || !str_ends_with($file, '.php')) {
            return false;
        }
        $this->invalidate($file);
        return opcache_compile_file($file);
    }

    final protected function invalidate(string $file): bool
    {
        if (!static::opCacheSupported() || !is_file($file) || !str_ends_with($file, '.php')) {
            return true;
        }
        return touch($file, static::COMPILE_OFFSET) && opcache_invalidate($file, true);
    }

    final protected function read(string $file): ?CacheEntry
    {
        static $handler;

        $handler = $handler ?? static function () {
                    return include func_get_arg(0);
                };

        try {
            if (is_file($file)) {
                return $handler($file);
            }
        } catch (Throwable $error) {
            $this->logger?->debug('Cache Miss ! A file failed to load.', [
                "driver" => static::class,
                "filename" => $file,
                "error" => $error,
            ]);
        }

        return null;
    }

    final protected function write(string $filename, string $contents): bool
    {
        $retry = 0;

        while ($retry < 3) {

            try {
                $this->setErrorHandler();
                if (!$this->tmpFile) {
                    $this->tmpFiles[] = $this->tmpFile = $this->root . DIRECTORY_SEPARATOR . uniqid('', true);
                }
                if ($this->mkdir(dirname($filename))) {

                    if (file_put_contents($this->tmpFile, $contents) !== false) {
                        return rename($this->tmpFile, $filename) && $this->chmod($filename);
                    }
                }
            } catch (\Throwable $error) {
                $this->tmpFile = null;
                $this->logger?->debug('Cache write error.', [
                    'driver' => static::class,
                    "filename" => $filename,
                    "retry" => ($retry + 1) . "/3",
                    "error" => $error
                ]);
            } finally { \restore_error_handler(); }
            $retry++;
        }

        return false;
    }

    final protected function getFilename(string $key, string $extension = '.php'): string
    {
        if (!empty($extension)) {
            $extension = str_starts_with($extension, '.') ? $extension : ".$extension";
        }

        $hash = $this->getHashedKey($key);
        return $this->root . DIRECTORY_SEPARATOR . $hash[0] . $hash[1] . DIRECTORY_SEPARATOR . $hash . $extension;
    }

    final protected function varExporter(mixed $data): ?string
    {
        if (is_scalar($data) || is_null($data)) {
            return var_export($data, true);
        } elseif (is_object($data)) {
            try {
                return VarExporter::export($data);
            } catch (\Throwable) {
                return null;
            }
        } elseif (is_array($data)) {
            $result = '[';
            foreach ($data as $key => $value) {
                $tmp = $this->varExporter($value);
                if ($tmp === null) return null;
                $result .= sprintf('%s=>%s,', var_export($key, true), $tmp);
            }
            return $result . ']';
        }
        return null;
    }

    public function purge(): void
    {

        foreach ($this->getFiles($this->root, 'php') as $fileName) {
            $canRemove = false;
            if ($entry = $this->read($fileName)) {
                $canRemove = !$entry->isHit();
            } else $canRemove = true;

            if ($canRemove) {
                $this->invalidate($fileName);
                $this->unlink($fileName);
            }
        }
    }

    public function clear(): bool
    {

        $result = true;

        foreach ($this->getFiles($this->root, 'php|txt') as $file) {
            $this->invalidate($file);
            $result = $this->unlink($file) && $result;

            $this->rmdir(dirname($file));
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        $fileName = $this->getFilename($key, '');
        $result = true;

        foreach (['php', 'txt'] as $ext) {
            $this->invalidate("{$fileName}.{$ext}");
            $result = (!is_file("{$fileName}.{$ext}") || $this->unlink("{$fileName}.{$ext}")) && $result;
        }

        return $result;
    }

    public function get(string $key): CacheEntry
    {
        if ($entry = $this->read($this->getFilename($key))) {
            return $entry;
        }
        return CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->get($key)->isHit();
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {

        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);

        if ($this->isExpired($expiry)) {
            return $this->delete($key);
        }

        $file = $this->getFilename($key, '');

        if (is_string($value) && mb_strlen($value) > self::STRING_SIZE_LIMIT) {
            $txtFile = basename($file) . '.txt';
            if ($this->write(dirname($file) . DIRECTORY_SEPARATOR . $txtFile, $value)) {
                $contents = sprintf(self::TEMPLATE_TXT, var_export($txtFile, true));
            } else return false;
        } else $contents = $this->varExporter($value);
        if (null !== $contents && $this->write($file . '.php', sprintf(self::TEMPLATE, $key, $expiry, $contents))) {
            $this->compile($file);
            return true;
        }

        return false;
    }

}
