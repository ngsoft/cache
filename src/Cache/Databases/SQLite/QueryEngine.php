<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases\SQLite;

use NGSOFT\Cache\Databases\Common,
    SQLite3Result;

abstract class QueryEngine extends Common
{

    public function createTable(string $table): bool
    {
        return $this->driver->exec(
                        sprintf(
                                'CREATE TABLE IF NOT EXISTS %s(%s TEXT PRIMARY KEY NOT NULL, %s BLOB, %s INTEGER, %s BLOB)',
                                $table, self::COLUMN_KEY, self::COLUMN_DATA, self::COLUMN_EXPIRY, self::COLUMN_TAGS
                        )
                ) !== false;
    }

    public function delete(string $key): bool
    {
        $statement = $this->prepare(sprintf(
                        'DELETE FROM %s WHERE %s = ?',
                        $this->table,
                        self::COLUMN_KEY
                ), [$key]
        );

        return $statement ? $statement->execute() instanceof SQLite3Result : false;
    }

    public function write(array $data): bool
    {
        $cols = $this->getColumns();
        $input = [
            'keys' => [],
            'bindings' => [],
        ];

        if (count($input) !== count($cols)) {
            return false;
        }

        foreach ($data as $key => $value) {
            if ( ! in_array($key, $cols)) {
                return false;
            }
            $input['keys'] [] = $key;
            $input['bindings'] [":{$key}"] = $value;
        }



        $statement = $this->prepare(
                sprintf(
                        'INSERT OR REPLACE INTO %s (%s) VALUES (:key, :data, :expiry, :tags)',
                        $this->table,
                        implode(',', $input['keys'])
                ), $input['bindings']
        );

        return $statement && $statement->execute() !== false;
    }

    public function clear(): bool
    {
        $statement = $this->prepare(
                sprintf('DELETE FROM %s', $this->table)
        );
        return $statement && $statement->execute() !== false;
    }

    public function purge(): bool
    {

        $statement = $this->prepare(
                sprintf(
                        'DELETE FROM %s WHERE %s > 0 AND %s < ?',
                        $this->table,
                        self::COLUMN_EXPIRY,
                        self::COLUMN_EXPIRY
                ), [time()]
        );

        return $statement && $statement->execute() !== false;
    }

}
