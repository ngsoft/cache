<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException;
use NGSOFT\Cache\{
    CacheDriver, FileSystem
};
use Psr\Log\LogLevel;

/**
 * This Adapter mainly exists to store binary files
 * It has compatibility with the serialiser but it's way slower than PHPFCache
 * But to store Images or other types of binary/text files it's way better
 *
 */
class FileCache extends FileSystem implements CacheDriver {

    /** {@inheritdoc} */
    protected function getExtension(): string {
        return '.bin';
    }

    /** {@inheritdoc} */
    protected function doSave($keysAndValues, int $expiry = 0): bool {
        $r = true;
        foreach ($keysAndValues as $key => $value) {
            $filename = $this->getFilename($key, $this->getExtension());
            if (
                    ($contents = $this->createFileContents($value, $expiry)) and
                    $this->write($filename, $contents)
            ) continue;

            $r = false;
        }
        return $r;
    }

    /** {@inheritdoc} */
    protected function read(string $filename, &$value = null): bool {
        $value = null;

        if (!is_file($filename)) return false;

        $success = false;

        try {
            $this->setErrorHandler();
            try {
                //generally it's where the error is thrown
                if (
                        $handle = fopen($filename, 'r') and
                        flock($handle, LOCK_SH)
                ) {

                    $line = fgets($handle);
                    if ($line !== false) {
                        $line = rtrim($line);
                        $line = explode('|', $line);
                        if (count($line) == 2) {
                            list($expiry, $ct) = $line;
                            $expire = (int) $expiry;
                            if (
                                    !$this->isExpired($expire) and
                                    in_array($ct, ['scalar', 'string', 'serializable'])
                            ) {
                                $data = '';
                                while (($line = fgets($handle)) !== false) {
                                    $data .= $line;
                                }
                                $data = $this->decode($data, $ct);
                                if (null !== $data) {
                                    $value = $data;
                                    $success = true;
                                }
                            }
                        }
                    }
                    flock($handle, LOCK_UN);
                    fclose($handle);
                }
            } catch (ErrorException $error) {

                $this->log(LogLevel::DEBUG, 'Cache Miss ! A file failed to load.', [
                    "classname" => static::class,
                    "filename" => $filename,
                    "error" => $error
                ]);
            }
        } finally {
            \restore_error_handler();
        }

        return $success;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Prep the content for saving
     *
     * @param mixed $value
     * @param int|null $expiry
     * @return string|null
     */
    protected function createFileContents($value, int $expiry = null): ?string {
        $expiry = max(0, $expiry ?? 0);
        if ($this->isExpired($expiry)) return null;
        if (
                $ct = $this->getContentType($value) and
                ($serialized = $this->encode($value)) !== null
        ) {
            //we just add a line on top of the contents 0|string
            return sprintf("%u|%s\n%s", $expiry, $ct, $serialized);
        }
        return null;
    }

    /**
     * Checks content type to use
     *
     * @param mixed $value
     * @return string|null
     */
    protected function getContentType($value): ?string {
        if (is_string($value)) return 'string';
        if (is_scalar($value)) return 'scalar';
        if (is_object($value) or is_array($value)) return 'serializable';
        return null;
    }

    /**
     * Exports value so that it can be saved
     * @param mixed $input
     * @return string|null
     */
    protected function encode($input): ?string {

        switch ($this->getContentType($input)) {
            case 'string':
                return $input;
            case 'scalar':
                return var_export($input, true);
            case 'serializable':
                return $this->safeSerialize($input);
        }
        return null;
    }

    /**
     * Decode input
     * @param string $input
     * @param string $method
     * @return string|null
     */
    protected function decode(string $input, string $method): ?string {
        switch ($method) {
            case 'string':
                return $input;
            case 'scalar':
                return json_decode($input);
            case 'serializable':
                return $this->safeUnserialize($input);
        }
        return null;
    }

}
