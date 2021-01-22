<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException;
use NGSOFT\Cache\{
    Driver, Utils\FileSystem
};
use Psr\Log\LogLevel;

/**
 * This driver mainly exists to store binary files
 * It has compatibility with the serialiser but it's slower than OPCacheDriver (even without opcache enabled)
 * But to store images or other types of binary/text files it's way better (prevents truncated datas on binaries)
 *
 */
final class FileDriver extends FileSystem implements Driver {

    /** {@inheritdoc} */
    protected function getExtension(): string {
        return '.bin';
    }

    ////////////////////////////   API   ////////////////////////////

    /** {@inheritdoc} */
    public function set(string $key, $value, int $expiry = 0): bool {

        if ($this->isExpired($expiry)) return $this->delete($key);
        $filename = $this->getFilename($key, $this->getExtension());
        if (
                ($contents = $this->createFileContents($value, $expiry)) and
                $this->write($filename, $contents)
        ) return true;

        return false;
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

                    // prevent further failed reads
                    if ($success == false) $this->unlink($filename);
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
    private function createFileContents($value, int $expiry = null): ?string {
        $expiry = max(0, $expiry ?? 0);
        if ($this->isExpired($expiry)) return null;

        if (
                $ct = $this->getContentType($value) and
                ($serialized = $this->encode($value)) !== null
        ) {
            //we just add a line on top of the contents eg: 0|string
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
    private function getContentType($input): ?string {
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
    private function encode($input): ?string {
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
     * @return mixed|null
     */
    private function decode(string $input, string $method) {
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
