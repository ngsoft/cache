<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use FilesystemIterator,
    Generator;
use NGSOFT\{
    Cache\CacheEntry, Cache\Exceptions\CacheError, Cache\Exceptions\InvalidArgument, Tools
};
use RecursiveDirectoryIterator,
    Throwable;
use function class_basename,
             mb_strlen,
             NGSOFT\Tools\some,
             str_ends_with,
             str_starts_with;

/**
 * The oldest cache driver that store binary datas
 */
class FileDriver extends BaseDriver
{

    protected const KEY_SERIALIZED = 3;
    protected const EXTENSION_META = '.json';
    protected const EXTENSION_FILE = '.txt';
    protected const STORAGE_PREFIX = '@';
    protected const CHMOD_DIR = 0777;
    protected const CHMOD_FILE = 0666;

    protected array $tmpFiles = [];
    protected ?string $tmpFile = null;

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
        if ( ! is_array($file)) {
            $file = [$file];
        }
        foreach ($file as $path) {
            $result = ( ! is_file($path) || unlink($path)) && $result;
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
        for ($i = 0; $i < strlen($charcodes); $i ++ ) {
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

        $extensions = ! is_array($extensions) ? [$extensions] : $extensions;
        $extensions = array_map(fn($ex) => (empty($ex) || str_starts_with($ex, '.')) ? $ex : ".$ex", $extensions);

        foreach ($this->getDirs($root) as $dir) {
            foreach (scandir($dir, SCANDIR_SORT_NONE) ?: [] as $file) {
                if ($file === '.' || $file === '..' || strpos($file, '.') !== 32) {
                    continue;
                }
                if (some(fn($extension) => str_ends_with($file, $extension), $extensions)) {
                    yield $file => $dir . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
    }

    protected function read(string $file): mixed
    {


        try {
            $this->setErrorHandler();
            return file_get_contents($file);
        } catch (Throwable $error) {

            $this->logger?->debug('Cache Miss ! A file failed to load.', [
                "driver" => static::class,
                "filename" => $file,
                "error" => $error,
            ]);
        } finally { restore_error_handler(); }

        return null;
    }

    protected function write(string $filename, string $contents): bool
    {
        $retry = 0;

        while ($retry < 3) {

            try {
                $this->setErrorHandler();
                if ( ! $this->tmpFile) {
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
            $retry ++;
        }

        return false;
    }

    protected function getFilename(string $key, string $extension = ''): string
    {
        if ( ! empty($extension)) {
            $extension = str_starts_with($extension, '.') ? $extension : ".$extension";
        }

        $hash = $this->getHashedKey($key);
        return $this->root . DIRECTORY_SEPARATOR . $hash[0] . $hash[1] . DIRECTORY_SEPARATOR . $hash . $extension;
    }

    public function __construct(
            protected string $root = '',
            protected string $prefix = ''
    )
    {
        try {
            $this->setErrorHandler();

            $this->prefix = ! empty($prefix) ? $prefix : strtolower(class_basename(static::class));
            $this->root = $this->normalizePath( ! empty($root) ? $root : sys_get_temp_dir());

            $this->mkdir($this->root);

            if ( ! is_dir($this->root) || ! is_writable($this->root)) {
                throw new CacheError(sprintf('Cannot use "%s" as root directory as it is not writable.', $root));
            }

            $this->root .= DIRECTORY_SEPARATOR . static::STORAGE_PREFIX . $this->prefix;
            $this->root = $this->normalizePath($this->root);

            if (self::onWindows() && mb_strlen($this->root) > 200) {
                throw new InvalidArgument(sprintf('Cache directory "%s" too long for windows filesystem.', $this->root));
            }

            if ( ! $this->mkdir($this->root)) {
                throw new CacheError(sprintf('Cannot create storage directory "%s".', $this->root));
            }
        } finally { restore_error_handler(); }
    }

    public function __destruct()
    {
        while ($file = array_pop($this->tmpFiles)) {
            $this->unlink($file);
        }
    }

    public function purge(): void
    {

        $toremove = [];

        foreach ($this->getFiles($this->root, self::EXTENSION_META) as $path) {

            $canremove = false;

            try {

                if ($contents = $this->read($path)) {
                    $meta = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
                    if ($this->isExpired($meta[self::KEY_EXPIRY])) {
                        $canremove = true;
                    }
                } else { $canremove = true; }
            } catch (\Throwable) {
                $canremove = true;
            }


            if ($canremove) {
                $toremove[] = $path;
                $toremove[] = preg_replace(sprintf('#\%s$#i', self::EXTENSION_META), self::EXTENSION_FILE, $path);
            }
        }

        $this->unlink($toremove);
    }

    public function clear(): bool
    {
        $result = true;
        foreach ($this->getFiles($this->root, [self::EXTENSION_META, self::EXTENSION_FILE]) as $path) {
            $result = $this->unlink($path) && $result;
            $this->rmdir(dirname($path));
        }
        return $result;
    }

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {
        $filename = $this->getFilename($key);
        $metafile = $filename . self::EXTENSION_META;
        $datafile = $filename . self::EXTENSION_FILE;
        $dirname = dirname($filename);
        if ($serialized = ! is_string($value)) {
            $value = \serialize($value);
        }
        $expiry = $this->lifetimeToExpiry($ttl);

        $meta = json_encode([
            self::KEY_EXPIRY => $expiry,
            self::KEY_VALUE => null,
            self::KEY_TAGS => $tags,
            self::KEY_SERIALIZED => $serialized
        ]);

        return $this->write($metafile, $meta) && $this->write($datafile, $value);
    }

    public function delete(string $key): bool
    {

        $result = true;
        foreach ([self::EXTENSION_META, self::EXTENSION_FILE] as $extension) {
            $path = $this->getFilename($key, $extension);
            $result = $this->unlink($path) && $result;
        }
        return $result;
    }

    public function getCacheEntry(string $key): CacheEntry
    {
        $filename = $this->getFilename($key);
        $metafile = $filename . self::EXTENSION_META;
        $datafile = $filename . self::EXTENSION_FILE;
        $meta = null;

        try {
            if ($contents = $this->read($metafile)) {
                $meta = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

                if ($data = $this->read($datafile)) {
                    if ($meta[self::KEY_SERIALIZED]) {
                        $data = $this->unserializeEntry($data);
                    }
                    $meta[self::KEY_VALUE] = $data;
                }
            }
        } catch (\Throwable) {
            $meta = null;
        }

        return $this->createCacheEntry($key, $meta);
    }

    public function has(string $key): bool
    {
        return $this->getCacheEntry($key)->isHit();
    }

    protected function getStats(): array
    {
        $usage = 0;
        $count = 0;
        foreach ($this->getFiles($this->root, [self::EXTENSION_META, self::EXTENSION_FILE]) as $path) {
            $usage += filesize($path) ?: 0;
            if (str_ends_with($path, self::EXTENSION_FILE)) {
                $count ++;
            }
        }


        return [
            'File Count' => $count,
            'File Usage' => Tools::getFilesize($usage),
            'Free Space' => Tools::getFilesize(disk_free_space($this->root) ?: 0),
        ];
    }

    public function __debugInfo(): array
    {
        return [
            'defaultLifetime' => $this->defaultLifetime,
            $this->root => $this->getStats(),
        ];
    }

}
