<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use FilesystemIterator,
    Generator;
use NGSOFT\Cache\{
    CacheEntry, Exceptions\CacheError, Exceptions\InvalidArgument
};
use RecursiveDirectoryIterator,
    Symfony\Component\VarExporter\VarExporter,
    Throwable;
use function mb_strlen,
             str_ends_with,
             str_starts_with;

class PhpDriver extends BaseDriver
{

    protected const STORAGE_PREFIX = '@';
    protected const CHMOD_DIR = 0777;
    protected const CHMOD_FILE = 0666;
    protected const COMPILE_OFFSET = -86400;
    protected const STRING_SIZE_LIMIT = 512000;
    protected const EXTENSION_PHP = '.php';
    protected const EXTENSION_TXT = '.txt';

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
        $result = $result ?? DIRECTORY_SEPARATOR === '\\';
        return $result;
    }

    protected function normalizePath(string $file): string
    {
        return preg_replace('#[\\\/]+#', DIRECTORY_SEPARATOR, $file);
    }

    protected function mkdir(string $dir): bool
    {
        return is_dir($dir) || mkdir($dir, self::CHMOD_DIR, true);
    }

    protected function rmdir(string $dir): bool
    {
        return is_dir($dir) && $this->isEmptyDir($dir) && rmdir($dir);
    }

    protected function unlink(string|array $file): bool
    {
        $result = true;
        if (!is_array($file)) {
            $file = [$file];
        }
        foreach ($file as $path) {
            $result = (!is_file($path) || unlink($path)) && $result;
        }
        return $result;
    }

    protected function chmod(string $file): bool
    {
        return is_file($file) && chmod($file, self::CHMOD_FILE);
    }

    protected function getHashedChar(): Generator
    {
        static $charcodes = '0123456789abcdef';
        for ($i = 0; $i < strlen($charcodes); $i++) {
            yield $charcodes[$i];
        }
    }

    protected function getDirs(string $root): Generator
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

    protected function isEmptyDir(string $dir): bool
    {
        $iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        return iterator_count($iterator) === 0;
    }

    protected function getFiles(string $root, string|array $extensions = []): Generator
    {

        $extensions = !is_array($extensions) ? [$extensions] : $extensions;
        $extensions = array_map(fn($ex) => (empty($ex) || str_starts_with($ex, '.')) ? $ex : ".$ex", $extensions);

        foreach ($this->getDirs($root) as $dir) {
            foreach (scandir($dir, SCANDIR_SORT_NONE) ?: [] as $file) {
                if ($file === '.' || $file === '..' || strpos($file, '.') !== 32) {
                    continue;
                }
                if ($this->some(fn($extension) => str_ends_with($file, $extension), $extensions)) {
                    yield $file => $dir . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
    }

    protected function compile(string $file): bool
    {
        if (!static::opCacheSupported() || !is_file($file) || !str_ends_with($file, self::EXTENSION_PHP)) {
            return false;
        }
        $this->invalidate($file);
        return opcache_compile_file($file);
    }

    protected function invalidate(string $file): bool
    {
        if (!static::opCacheSupported() || !is_file($file) || !str_ends_with($file, self::EXTENSION_PHP)) {
            return true;
        }
        return touch($file, static::COMPILE_OFFSET) && opcache_invalidate($file, true);
    }

    protected function read(string $file): mixed
    {
        static $handler;

        if (!$handler) {
            $handler = static function () {
                return require func_get_arg(0);
            };
        }

        try {
            return $handler($file);
        } catch (Throwable $error) {
            $this->logger?->debug('Cache Miss ! A file failed to load.', [
                "driver" => static::class,
                "filename" => $file,
                "error" => $error,
            ]);
        }

        return null;
    }

    protected function write(string $filename, string $contents): bool
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
            } catch (Throwable $error) {
                // tmpFile busy?
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

    protected function getFilename(string $key, string $extension = ''): string
    {
        if (!empty($extension)) {
            $extension = str_starts_with($extension, '.') ? $extension : ".$extension";
        }

        $hash = $this->getHashedKey($key);
        return $this->root . DIRECTORY_SEPARATOR . $hash[0] . $hash[1] . DIRECTORY_SEPARATOR . $hash . $extension;
    }

    protected function varExporter(mixed $data): ?string
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
                if (is_int($key)) {
                    $result .= sprintf('%s,', $tmp);
                } else { $result .= sprintf('%s=>%s,', var_export($key, true), $tmp); }
            }
            return $result . ']';
        }
        return null;
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
                throw new InvalidArgument(sprintf('Cache directory "%s" too long for windows filesystem.', $this->root));
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

    public function purge(): void
    {

        $toremove = [];
        foreach ($this->getFiles($this->root) as $path) {

            $canremove = false;
            $txtpath = $data = $this->read($path);
            if (!$data) $canremove = true;
            elseif ($this->isExpired($data[self::KEY_EXPIRY])) {
                $canremove = true;
            }
            if ($canremove) {
                $toremove[] = $path;
                $toremove[] = preg_replace('#php$#', 'txt', $path);
            }
        }
        $this->unlink($toremove);
    }

    public function clear(): bool
    {
        $result = true;

        foreach ($this->getFiles($this->root, [self::EXTENSION_PHP, self::EXTENSION_TXT]) as $path) {
            $this->invalidate($path);
            $result = $this->unlink($path) && $result;
            $this->rmdir(dirname($path));
        }
        return $result;
    }

    protected function doSet(string $key, mixed $value, int $expiry, array $tags): bool
    {


        $filename = $this->getFilename($key);
        $dirname = dirname($filename);

        if (is_string($value) && mb_strlen($value) > self::STRING_SIZE_LIMIT) {
            $txtFile = basename($filename) . self::EXTENSION_TXT;

            if ($this->write($dirname . DIRECTORY_SEPARATOR . $txtFile, $value)) {
                $contents = sprintf('file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . %s )', var_export($txtFile, true));
            } else return false;
        } else { $contents = $this->varExporter($value); }

        if (null === $contents) {
            return false;
        }

        $fileContents = "<?php\nreturn ";
        if ($expiry > 0) {
            $fileContents .= sprintf('microtime(true) > %d ? null : ', $expiry);
        }

        $fileContents .= "[\n";
        $fileContents .= sprintf("    %d,\n", $expiry);
        $fileContents .= sprintf("    %s,\n", $contents);
        $fileContents .= sprintf("    %s,\n", json_encode($tags));
        $fileContents .= "];";

        $phpFile = $filename . self::EXTENSION_PHP;
        if (is_file($phpFile)) {
            $this->invalidate($phpFile);
        }

        if ($this->write($phpFile, $fileContents)) {
            $this->compile($phpFile);
            return true;
        }

        return false;
    }

    public function delete(string $key): bool
    {
        $result = true;
        foreach ([self::EXTENSION_PHP, self::EXTENSION_TXT] as $extension) {

            $path = $this->getFilename($key, $extension);
            $this->invalidate($path);
            $result = $this->unlink($path) && $result;
        }
        return $result;
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        return $this->createCacheEntry($key, $this->read($this->getFilename($key, self::EXTENSION_PHP)));
    }

    public function has(string $key): bool
    {
        return $this->getCacheEntry($key)->isHit();
    }

}
