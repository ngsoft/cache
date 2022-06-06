<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\CacheEntry,
    SQLite3,
    SQLite3Result,
    Throwable;

/**
 * Uses Sqlite3 database as storage
 */
class Sqlite3Driver extends BaseCacheDriver
{

    protected const COLUMN_KEY = 'id';
    protected const COLUMN_DATA = 'data';
    protected const COLUMN_EXPIRY = 'expiry';

    protected SQLite3 $driver;

    public function __construct(
            SQLite3|string $driver = '',
            protected readonly string $table = 'cache'
    )
    {
        $driver = empty($driver) ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . $table . '.db3' : $driver;
        $this->driver = is_string($driver) ? new \SQLite3($driver) : $driver;
        $this->createTable($this->table);
    }

    protected function createTable(string $table): void
    {

        $this->driver->exec(
                sprintf(
                        'CREATE TABLE IF NOT EXISTS %s(%s TEXT PRIMARY KEY NOT NULL, %s BLOB, %s INTEGER)',
                        $table,
                        static::COLUMN_KEY,
                        static::COLUMN_DATA,
                        static::COLUMN_EXPIRY
                )
        );
    }

    protected function getColumns(): array
    {
        static $columns;
        $columns = $columns ?? [
            static::COLUMN_KEY,
            static::COLUMN_DATA,
            static::COLUMN_EXPIRY
        ];

        return $columns;
    }

    protected function find(string $key, bool $withData = true): ?array
    {

        $columns = $this->getColumns();

        if (!$withData) {
            unset($columns[1]);
        }

        $query = $this->driver->prepare(
                sprintf('SELECT %s FROM %s WHERE %s = :key LIMIT 1',
                        implode(',', $columns),
                        $this->table,
                        $columns[0]
                )
        );

        $query->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $query->execute()->fetchArray(SQLITE3_ASSOC);

        if (false === $result) {
            return null;
        }

        if ($this->isExpired($result[static::COLUMN_EXPIRY])) {
            $this->delete($key);
            return null;
        }

        return $result;
    }

    final protected function unserializeEntry(mixed $value): mixed
    {

        try {
            $this->setErrorHandler();
            if (!is_string($value)) {
                return $value;
            }

            if (!preg_match('#^[idbsaO]:#', $value)) {

                try {
                    return json_decode($value, flags: JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    return $value;
                }
            }

            if ($value === 'b:0;') {
                return false;
            }

            if (($result = \unserialize($value)) === false) {
                return null;
            }

            return $result;
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

    public function clear(): bool
    {
        return $this->driver->exec(sprintf('DELETE FROM %s', $this->table));
    }

    public function purge(): void
    {


        $query = $this->driver->prepare(
                sprintf(
                        'DELETE FROM %s WHERE %s > 0 AND %s < :now',
                        $this->table,
                        self::COLUMN_EXPIRY,
                        self::COLUMN_EXPIRY
                )
        );

        $query->bindValue(':now', time());

        $query->execute();
    }

    public function delete(string $key): bool
    {


        $query = $this->driver->prepare(
                sprintf(
                        'DELETE FROM %s WHERE %s = :key',
                        $this->table,
                        $this->getColumns()[0]
                )
        );

        $query->bindValue(':key', $key, SQLITE3_TEXT);
        return $query->execute() instanceof SQLite3Result;
    }

    public function get(string $key): CacheEntry
    {
        $result = CacheEntry::createEmpty($key);

        if ($item = $this->find($key)) {
            $result->expiry = $item[self::COLUMN_EXPIRY];
            $result->value = $this->unserializeEntry($item[self::COLUMN_DATA]);
        }
        return $result;
    }

    public function has(string $key): bool
    {
        return $this->find($key, false) !== null;
    }

    public function set(string $key, mixed $value, int $expiry = 0): bool
    {
        $expiry = $expiry === 0 ? 0 : $expiry;
        if ($this->defaultLifetime > 0) $expiry = min($expiry, time() + $this->defaultLifetime);


        if ($this->isExpired($expiry) || null === $value) {
            return $this->delete($key);
        }

        $query = $this->driver->prepare(
                sprintf(
                        'INSERT OR REPLACE INTO %s (%s) VALUES (:key, :data, :expiry)',
                        $this->table,
                        implode(',', $this->getColumns())
                )
        );
        $query->bindValue(':key', $key, SQLITE3_TEXT);
        $query->bindValue(':data', $this->serializeEntry($value), SQLITE3_BLOB);
        $query->bindValue(':expiry', $expiry);

        return $query->execute() instanceof SQLite3Result;
    }

}
