<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException,
    FilesystemIterator,
    Generator;
use NGSOFT\Cache\{
    BaseDriver, CacheException, InvalidArgumentException
};
use Psr\Log\LogLevel,
    RecursiveDirectoryIterator,
    RuntimeException,
    Traversable;
use function mb_strlen,
             str_ends_with,
             str_starts_with;

/**
 * Defines the methods that are specific to FileSystem Drivers
 */
abstract class FileSystem extends BaseDriver {

    /**
     * PREFIX for the namespace
     */
    private const STORAGE_PREFIX = '@';

    /**
     * Chmod assigned to directories
     */
    private const CHMOD_DIRECTORY = 0775;

    /**
     * Chmod assigned to regular files
     */
    private const CHMOD_FILE = 0664;

    /**
     * DIRECTORY_SEPARATOR
     * @var string
     */
    protected const DS = \DIRECTORY_SEPARATOR;

    /**
     * NAMESPACE_SEPARATOR
     * @var string
     */
    protected const NS = '\\';

    /** @var bool|null */
    private static $runOnWindows;

    /**
     * References main storage root
     * @var string
     */
    private $cacheRoot;

    /**
     * References the tmp file used to write data on
     * @var string|null
     */
    private $tmp;

    /**
     * References the tmp files issued previously (for garbage collection)
     * @var string[]
     */
    private $tmpfiles = [];

    /**
     * References the number of times write tried to create a file
     * max 3
     *
     * @var int|null
     */
    private $retry;

    /**
     * @param string|null $root if not set will use filesystem temp directory
     * @param string|null $prefix if not set will use the default prefix (lowercased driver relative class name)
     */
    public function __construct(
            string $root = null,
            string $prefix = null
    ) {
        $this->initialize($root, $prefix);
    }

    /**
     * Removes corrupted tmp files (on write miss)
     */
    public function __destruct() {
        while ($tmp = array_shift($this->tmpfiles)) {
            $this->unlink($tmp);
        }
    }

    /**
     * Prepares filesystem to store files
     *
     * @param string|null $root Root Directory defaults to tmp directory if not provided
     * @param string|null $prefix Adapter Prefix ($root/@$prefix)
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    final protected function initialize(string $root = null, string $prefix = null): void {

        if (!empty($prefix) and preg_match('#[^-_.A-Za-z0-9]#', $prefix, $match) > 0) {
            throw new InvalidArgumentException(sprintf('Prefix contains "%s" but only characters in [-_.A-Za-z0-9] are allowed.', $match[0]));
        }

        if (empty($prefix)) {
            $classname = static::class;
            $prefix = strtolower(substr($classname, 1 + strrpos($classname, self::NS)));
        }


        $root = $root ?? sys_get_temp_dir();

        //creates rootdir if not exists 0777 for parent directories
        @mkdir($root, 0777, true);

        if (
                !is_dir($root) or
                !is_writable($root)
        ) {
            throw new CacheException(sprintf('Cannot use "%s" as root directory as it is not writable.', $root));
        }

        $root = realpath($root); // removes trailing DS and normalizes

        $rootDir = $root . self::DS . self::STORAGE_PREFIX . $prefix;


        if ($this->isRunningOnWindows() and mb_strlen($rootDir) > 200) {
            throw new InvalidArgumentException(sprintf('Cache directory too long for windows filesystem (%s).', $rootDir));
        }

        if (!$this->mkdir($rootDir)) {
            throw new CacheException(sprintf('Cannot create storage directory (%s).', $rootDir));
        }
        $this->cacheRoot = $rootDir;
    }

    ////////////////////////////   Implementation   ////////////////////////////

    /**
     * Defines Filename Extension
     * @return string
     */
    abstract protected function getExtension(): string;

    /**
     * Reads contents from filename
     *
     * @param string $filename
     * @param mixed $value value passed by reference
     * @return bool
     */
    abstract protected function read(string $filename, &$value = null): bool;

    /** {@inheritdoc} */
    protected function doClear(): bool {
        $r = true;
        foreach ($this->scanFiles($this->getCacheRoot(), $this->getExtension()) as $file) {
            $r = $this->unlink($file) && $r;
        }
        foreach ($this->scanDirs($this->getCacheRoot()) as $dir) $this->rmdir($dir);
        return $r;
    }

    /** {@inheritdoc} */
    protected function doContains(string $key): bool {
        return $this->read($this->getFilename($key, $this->getExtension()));
    }

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        if (empty($keys)) return true;
        $r = true;
        foreach ($keys as $key) {
            $filename = $this->getFilename($key, $this->getExtension());
            if (is_file($filename)) $r = $this->unlink($filename) && $r;
        }
        return $r;
    }

    /** {@inheritdoc} */
    protected function doFetch(string ...$keys): Traversable {
        if (empty($keys)) return;
        foreach ($keys as $key) {
            if ($this->read($this->getFilename($key, $this->getExtension()), $value)) {
                yield $key => $value;
            } else yield $key => null;
        }
    }

    /** {@inheritdoc} */
    public function purge(): bool {
        $r = true;
        foreach ($this->scanFiles($this->getCacheRoot(), $this->getExtension()) as $file) {
            if (!$this->read($file)) $r = $this->unlink($file) && $r;
        }
        return $r;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Get currently assigned CacheRoot (with prefix)
     *
     * @return string
     */
    final protected function getCacheRoot(): string {
        return $this->cacheRoot;
    }

    /**
     * Get hashed file fullpath corresponding to the key
     *
     * @param string $key
     * @param string|null $extension with or without extension
     * @return string
     */
    final protected function getFilename(string $key, string $extension = null): string {
        if (!empty($extension)) $extension = str_starts_with($extension, '.') ? $extension : ".$extension";
        else $extension = '';
        $hash = $this->getHashedKey($key);
        return $this->getCacheRoot() . self::DS . $hash[0] . $hash[1] . self::DS . $hash . $extension;
    }

    /**
     * Tells Whenever we are using windows
     * @return bool
     */
    final protected function isRunningOnWindows(): bool {
        return self::$runOnWindows = self::$runOnWindows ?? self::DS == self::NS;
    }

    /**
     * Makes directory
     *
     * @param string $dir The directory path.
     * @param bool $recursive Allows the creation of nested directories
     * @return bool true if dir exists or dir created
     */
    final protected function mkdir(string $dir, bool $recursive = false): bool {
        return is_dir($dir) or @mkdir($dir, self::CHMOD_DIRECTORY, $recursive);
    }

    /**
     * Removes a directory if it is empty
     *
     * @param string $dir
     * @return bool
     */
    final protected function rmdir(string $dir): bool {
        return
                is_dir($dir) and
                $this->isEmptyDirectory($dir) and
                @rmdir($dir);
    }

    /**
     * Changes file mode
     *
     * @param string $file Path to the regular file.
     * @return bool true if regular file and mode changed
     */
    final protected function chmod(string $file): bool {
        return
                is_file($file) and
                @chmod($file, self::CHMOD_FILE);
    }

    /**
     * Removes the file
     *
     * @param string $file
     * @return bool
     */
    final protected function unlink(string $file): bool {
        // same effect, file not there anymore
        return
                !file_exists($file) or
                @unlink($file);
    }

    /**
     * Checks if directory is empty
     *
     * @link https://stackoverflow.com/questions/7497733/how-can-i-use-php-to-check-if-a-directory-is-empty
     * @param string $dir
     * @return bool true if is a directory and has no files
     */
    final protected function isEmptyDirectory(string $dir): bool {
        try {
            $iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
            return iterator_count($iterator) === 0;
        } catch (RuntimeException $error) {
            //  UnexpectedValueException extends RuntimeException
            $error->getCode();
            return false;
        }
    }

    /**
     * Scan input dir for cached files
     *
     * @param string $dir
     * @param string|null $extension extension to list
     * @return Generator
     */
    final protected function scanFiles(string $dir, string $extension = null): Generator {

        if (is_string($extension)) {
            $extension = str_starts_with($extension, '.') ? $extension : ".$extension";
        }

        foreach ($this->scanDirs($dir) as $folder) {
            foreach (scandir($folder, SCANDIR_SORT_NONE) ?: [] as $file) {
                if (
                        is_file($path = ($folder . self::DS . $file)) and
                        strpos($file, '.') === 32 and
                        ($extension === null or str_ends_with($file, $extension))
                ) yield $file => $path;
            }
        }
    }

    /**
     * Scan input dir for hashed subdirs
     *
     * @param string $dir
     * @return Generator
     */
    final protected function scanDirs(string $dir): Generator {
        if (!is_dir($dir)) return;
        for ($i = 0; $i < 16; $i++) {
            for ($j = 0; $j < 16; $j++) {
                $file = self::HASH_CHARCODES[$i] . self::HASH_CHARCODES[$j];
                if (!is_dir($path = $dir . self::DS . $file)) continue;
                yield $file => $path;
            }
        }
    }

    /**
     * Save contents into filename
     *
     * @suppress PhanPossiblyInfiniteRecursionSameParams
     * @staticvar int $cnt
     * @param string $filename
     * @param string $contents
     * @return bool
     */
    protected function write(string $filename, string $contents): bool {
        $this->retry = $this->retry ?? 0;
        try {
            $this->setErrorHandler();
            set_time_limit(60);

            try {

                if ($this->tmp === null) $this->tmpfiles[] = $this->tmp = $this->getCacheRoot() . self::DS . uniqid('', true);
                if (!is_dir(dirname($filename))) $this->mkdir(dirname($filename));
                if (file_put_contents($this->tmp, $contents) !== false) {
                    $this->retry = 0;
                    return
                            rename($this->tmp, $filename) and
                            $this->chmod($filename);
                }
                return false;
            } catch (ErrorException $error) {
                $this->log(LogLevel::DEBUG, 'Cache write error', [
                    "driver" => static::class,
                    "filename" => $filename,
                    "retry" => $this->retry . "/3",
                    "error" => $error
                ]);
                $this->tmp = null;
            }
        } finally { \restore_error_handler(); }
        //retry 3 times
        $this->retry++;
        if ($this->retry < 3) return $this->write($filename, $contents);
        $this->retry = 0;
        return false;
    }

}
