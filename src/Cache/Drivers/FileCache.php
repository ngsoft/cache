<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException;
use NGSOFT\Cache\{
    CacheDriver, CacheObject, FileSystem
};
use Psr\Log\LogLevel;

/**
 * This Adapter mainly exists to store binary files
 * It has compatibility with the serialiser but it's way slower than OPHPCache
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
                        $handle = fopen($filename, 'rb') and
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
                                    in_array($ct, ['scalar', 'string', 'serializable', CacheObject::class])
                            ) {
                                if ($ct === CacheObject::class) {
                                    $obj = $this->safeUnserialize(rtrim(fgets($handle)));
                                    if (is_array($obj)) {
                                        $value = new CacheObject($obj['k']);
                                        $value->expiry = $obj['e'];
                                        $value->tags = $obj['t'];
                                        $ct = $obj['c'];
                                    }
                                }
                                $data = '';
                                while (($line = fgets($handle)) !== false) {
                                    $data .= $line;
                                }
                                $data = $this->decode($data, $ct);
                                if (null !== $data) {
                                    if ($value instanceof CacheObject) {
                                        $value->value = $data;
                                    } else $value = $data;
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
        } finally { \restore_error_handler(); }

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
     * @param mixed $input
     * @return string|null
     */
    protected function getContentType($input): ?string {
        if ($input instanceof CacheObject) return CacheObject::class;
        if (is_string($input)) return 'string';
        if (is_scalar($input)) return 'scalar';
        if (is_object($input) or is_array($input)) return 'serializable';
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
            case CacheObject::class:
                /** @var CacheObject $input */
                if (
                        ($ct = $this->getContentType($input->value)) and
                        ($value = $this->encode($input->value)) !== null
                ) {
                    $obj = [
                        'k' => $input->key,
                        'c' => $ct,
                        'e' => $input->expiry,
                        't' => $input->tags,
                    ];

                    if ($serialized = $this->safeSerialize($obj)) {
                        return sprintf("%s\n%s", $serialized, $value);
                    }
                }
        }

        return null;
    }

    /**
     * Decode input
     * @param string $input
     * @param string $method
     * @return mixed|null
     */
    protected function decode(string $input, string $method) {
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
