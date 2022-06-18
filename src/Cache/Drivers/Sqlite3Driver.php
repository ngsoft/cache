<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use JsonException;
use NGSOFT\{
    Cache\CacheEntry, Cache\Databases\DatabaseAdapter, Cache\Databases\SQLite\PDOAdapter, Cache\Databases\SQLite\QueryEngine, Cache\Databases\SQLite\SQLite3Adapter,
    Cache\Exceptions\InvalidArgument, Tools
};
use PDO,
    SQLite3,
    Throwable;

class Sqlite3Driver extends BaseDriver
{

    protected QueryEngine $driver;

    /**
     *
     * @param SQLite3|PDO|string $driver A SQLite3 instance or a filename
     * @param string $table
     */
    public function __construct(
            SQLite3|PDO|string $driver = '',
            protected readonly string $table = 'cache'
    )
    {

        $driver = empty($driver) ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . $table . '.db3' : $driver;

        if (is_string($driver)) {
            if (class_exists(\SQLite3::class)) {
                $driver = new \SQLite3($driver);
            } else { $driver = new PDO(sprintf('sqlite:%s', $driver)); }
        }

        if ($driver instanceof PDO) {
            $type = $driver->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($type !== 'sqlite') {
                throw new InvalidArgument(sprintf('Invalid PDO driver, sqlite requested, %s given.', $type));
            }
            $this->driver = new PDOAdapter($driver, $table);
        } else { $this->driver = new SQLite3Adapter($driver, $table); }
        $this->driver->createTable($table);
    }

    protected function unserializeEntry(mixed $value): mixed
    {

        try {
            $this->setErrorHandler();

            if ($this->isSerialized($value)) {
                if ($value === 'b:0;') {
                    return false;
                }

                if (($result = \unserialize($value)) === false) {
                    return null;
                }

                return $result;
            }
            try {
                return json_decode($value, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $value;
            }
        } catch (Throwable) { return null; } finally { restore_error_handler(); }
    }

    final protected function serializeEntry(mixed $value): mixed
    {
        try {
            $this->setErrorHandler();

            if (is_string($value)) {
                return $value;
            }

            if (is_scalar($value)) {
                return json_encode($value);
            }
            return \serialize($value);
        } catch (Throwable) { return null; } finally { restore_error_handler(); }
    }

    protected function doSet(string $key, mixed $value, ?int $ttl, array $tags): bool
    {
        return $this->driver->write([
                    DatabaseAdapter::COLUMN_KEY => $key,
                    DatabaseAdapter::COLUMN_DATA => $this->serializeEntry($value),
                    DatabaseAdapter::COLUMN_EXPIRY => $this->lifetimeToExpiry($ttl),
                    DatabaseAdapter::COLUMN_TAGS => json_encode($tags),
        ]);
    }

    public function purge(): void
    {

        $this->driver->purge();
    }

    public function clear(): bool
    {
        return $this->driver->clear();
    }

    public function delete(string $key): bool
    {
        return $this->driver->delete($key);
    }

    public function getCacheEntry(string $key): CacheEntry
    {

        $this->purge();

        if ($item = $this->driver->read($key)) {

            return $this->createCacheEntry($key, [
                        self::KEY_EXPIRY => $item[DatabaseAdapter::COLUMN_EXPIRY],
                        self::KEY_VALUE => $this->unserializeEntry($item[DatabaseAdapter::COLUMN_DATA]),
                        self::KEY_TAGS => json_decode($item[DatabaseAdapter::COLUMN_TAGS], true),
            ]);
        }

        return CacheEntry::createEmpty($key);
    }

    public function has(string $key): bool
    {
        return $this->driver->read($key, false) !== false;
    }

    public function __debugInfo(): array
    {




        $filename = $this->driver->getFilename();

        return [
            'defaultLifetime' => $this->defaultLifetime,
            $filename . "[{$this->table}]" => [
                'File Size' => Tools::getFilesize(filesize($filename) ?: 0),
                "Cache Entries" => count($this->driver),
            ],
        ];
    }

}
