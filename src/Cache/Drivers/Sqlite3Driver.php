<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use JsonException;
use NGSOFT\{
    Cache\CacheEntry, Cache\Databases\DatabaseAdapter, Tools
};
use SQLite3,
    SQLite3Result,
    Throwable;

class Sqlite3Driver extends BaseDriver
{

    protected SQLite3 $driver;

    /**
     *
     * @param SQLite3|string $provider A SQLite3 instance or a filename
     * @param string $table
     */
    public function __construct(
            SQLite3|string $provider = '',
            protected readonly string $table = 'cache'
    )
    {
        $provider = empty($provider) ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . $table . '.db3' : $provider;
        $this->driver = is_string($provider) ? new SQLite3($provider) : $provider;
        $this->createTable($this->table);
    }

    protected function createTable(string $table): void
    {

        $this->driver->exec(
                sprintf(
                        'CREATE TABLE IF NOT EXISTS %s(%s TEXT PRIMARY KEY NOT NULL, %s BLOB, %s INTEGER, %s BLOB)',
                        $table,
                        DatabaseAdapter::COLUMN_KEY,
                        DatabaseAdapter::COLUMN_DATA,
                        DatabaseAdapter::COLUMN_EXPIRY,
                        DatabaseAdapter::COLUMN_TAGS,
                )
        );
    }

    protected function getColumns(): array
    {
        static $columns;
        $columns = $columns ?? [
            DatabaseAdapter::COLUMN_KEY,
            DatabaseAdapter::COLUMN_DATA,
            DatabaseAdapter::COLUMN_EXPIRY,
            DatabaseAdapter::COLUMN_TAGS,
        ];

        return $columns;
    }

    protected function find(string $key, bool $withData = true): ?array
    {

        if ($withData) {
            $columns = $this->getColumns();
        } else $columns = [DatabaseAdapter::COLUMN_KEY, DatabaseAdapter::COLUMN_EXPIRY];



        try {
            $this->setErrorHandler();

            $query = $this->driver->prepare(
                    sprintf('SELECT %s FROM %s WHERE %s = :key LIMIT 1',
                            implode(',', $columns),
                            $this->table,
                            $columns[0]
                    )
            );

            $query->bindValue(':key', $key, SQLITE3_TEXT);
            //there can return false twice
            $result = $query->execute()->fetchArray(SQLITE3_ASSOC);

            if (false === $result) {
                return null;
            }

            if ($this->isExpired($result[DatabaseAdapter::COLUMN_EXPIRY])) {
                $this->delete($key);
                return null;
            }

            return $result;
        } catch (\Throwable) {
            return null;
        } finally { \restore_error_handler(); }
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


        try {
            $this->setErrorHandler();
            $query = $this->driver->prepare(
                    sprintf(
                            'INSERT OR REPLACE INTO %s (%s) VALUES (:key, :data, :expiry, :tags)',
                            $this->table,
                            implode(',', $this->getColumns())
                    )
            );
            $query->bindValue(':key', $key, SQLITE3_TEXT);
            $query->bindValue(':data', $this->serializeEntry($value), SQLITE3_BLOB);
            $query->bindValue(':expiry', $this->lifetimeToExpiry($ttl), SQLITE3_INTEGER);
            $query->bindValue(':tags', json_encode($tags), SQLITE3_BLOB);

            return $query->execute() instanceof SQLite3Result;
        } catch (\Throwable) {
            return false;
        } finally { \restore_error_handler(); }
    }

    public function purge(): void
    {

        try {
            $this->setErrorHandler();

            $query = $this->driver->prepare(
                    sprintf(
                            'DELETE FROM %s WHERE %s > 0 AND %s < :now',
                            $this->table,
                            DatabaseAdapter::COLUMN_EXPIRY,
                            DatabaseAdapter::COLUMN_EXPIRY
                    )
            );

            $query->bindValue(':now', time());
            $query->execute();
        } catch (\Throwable) {

        } finally { \restore_error_handler(); }
    }

    public function clear(): bool
    {
        try {
            $this->setErrorHandler();
            return $this->driver->exec(sprintf('DELETE FROM %s', $this->table));
        } catch (\Throwable) {
            return false;
        } finally { \restore_error_handler(); }
    }

    public function delete(string $key): bool
    {

        try {
            $this->setErrorHandler();

            $query = $this->driver->prepare(
                    sprintf(
                            'DELETE FROM %s WHERE %s = :key',
                            $this->table,
                            DatabaseAdapter::COLUMN_KEY
                    )
            );

            $query->bindValue(':key', $key, SQLITE3_TEXT);
            return $query->execute() instanceof SQLite3Result;
        } catch (\Throwable) {
            return false;
        } finally { \restore_error_handler(); }
    }

    public function getCacheEntry(string $key): CacheEntry
    {

        $this->purge();

        if ($item = $this->find($key)) {

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
        return $this->find($key, false) !== null;
    }

    public function __debugInfo(): array
    {

        $filename = $this->driver->query('PRAGMA database_list')->fetchArray(SQLITE3_ASSOC)['file'];

        $count = $this->driver->querySingle(sprintf('SELECT COUNT(*) as count FROM %s', $this->table));

        return [
            'defaultLifetime' => $this->defaultLifetime,
            $filename . "[{$this->table}]" => [
                'File Size' => Tools::getFilesize(filesize($filename) ?: 0),
                "Cache Entries" => $count,
            ],
        ];
    }

}
