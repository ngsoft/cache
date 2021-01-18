<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use FilesystemIterator,
    Generator;
use NGSOFT\Cache\{
    BaseDriver, CacheException, InvalidArgumentException
};
use RecursiveDirectoryIterator,
    RuntimeException;
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
    protected const STORAGE_PREFIX = '@';

    /**
     * Chmod assigned to directories
     */
    protected const CHMOD_DIRECTORY = 0775;

    /**
     * Chmod assigned to regular files
     */
    protected const CHMOD_FILE = 0664;

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
    protected static $runOnWindows;

    /**
     * References main storage root
     * @var string
     */
    protected $cacheRoot;

    /**
     * References the tmp file used to write data on
     * @var string|null
     */
    protected $tmp;

    /**
     * References the tmp files issued previously (for garbage collection)
     * @var string[]
     */
    protected $tmpfiles = [];

    /**
     * @param string $root if not set will use filesystem temp directory
     * @param string $prefix if not set will use the default prefix (lowercased adapter relative class name)
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

        if (empty($prefix)) {
            $classname = static::class;
            $prefix = strtolower(substr($classname, 1 + strrpos($classname, self::NS)));
        }
        if (preg_match('#[^-_.A-Za-z0-9]#', $prefix, $match) > 0) {
            throw new InvalidArgumentException(sprintf('Prefix contains "%s" but only characters in [-_.A-Za-z0-9] are allowed.', $match[0]));
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

    /**
     * Get currently assigned CacheRoot (with prefix)
     *
     * @return string
     */
    final protected function getCacheRoot(): string {
        return $this->cacheRoot;
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
        return is_dir($dir) or @mkdir($dir, static::CHMOD_DIRECTORY, $recursive);
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
                @chmod($file, static::CHMOD_FILE);
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
                        is_file($path = $folder . self::DS . $file) and
                        strpos($file, '.') === 20 and
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
                $file = self::HASH_CARCODES[$i] . self::HASH_CARCODES[$j];
                if (!is_dir($path = $dir . self::DS . $file)) continue;
                yield $file => $path;
            }
        }
    }

}
