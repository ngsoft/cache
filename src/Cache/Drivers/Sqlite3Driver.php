<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\Cache\CacheEntry,
    SQLite3,
    SQLite3Result;

class Sqlite3Driver extends BaseCacheDriver
{

    protected const COLUMN_KEY = 'id';
    protected const COLUMN_DATA = 'data';
    protected const COLUMN_EXPIRY = 'expiry';

    public function __construct(
            protected SQLite3 $driver,
            protected readonly string $table = 'cache'
    )
    {
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
        static $columns = [
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

    public function clear(): bool
    {
        return $this->driver->exec(sprintf('DELETE FROM %s', $this->table));
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
            $result->value = unserialize($item[self::COLUMN_DATA]);
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

        $query = $this->driver->prepare(
                sprintf(
                        'INSERT OR REPLACE INTO %s (%s) VALUES (:key, :data, :expiry)',
                        $this->table,
                        implode(',', $this->getColumns())
                )
        );
        $query->bindValue(':key', $key, SQLITE3_TEXT);
        $query->bindValue(':data', serialize($value), SQLITE3_BLOB);
        $query->bindValue(':expiry', $expiry);

        return $query->execute() instanceof SQLite3Result;
    }

}
