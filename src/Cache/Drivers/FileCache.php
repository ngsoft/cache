<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\{
    CacheDriver, FileSystem
};

/**
 * This Adapter mainly exists to store binary files
 * It has compatibility with the serialiser but it's way slower than PHPFCache
 * But to store Images or other types of binary/text files it's way better
 *
 */
class FileCache extends FileSystem implements CacheDriver {

    protected function doSave($keysAndValues, int $expiry = 0): bool {

    }

    /** {@inheritdoc} */
    protected function getExtension(): string {
        return '.bin';
    }

    protected function read(string $filename, &$value = null): bool {

    }

    ////////////////////////////   Utils   ////////////////////////////



    protected function getContentType($value): ?string {

    }

}
