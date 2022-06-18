<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases\SQLite;

class SQLite3Adapter extends QueryEngine
{

    public function __construct(
            \SQLite3 $driver,
            string $table
    )
    {
        $this->table = $table;
        $this->driver = $driver;
    }

    public function read(string $key, bool $data = true): array|false
    {

        if ($data) {
            $columns = $this->getColumns();
        } else $columns = [self::COLUMN_KEY, self::COLUMN_EXPIRY];



        if (
                $statement = $this->prepare(sprintf(
                        'SELECT %s FROM %s WHERE %s = :key LIMIT 1',
                        implode(',', $columns),
                        $this->table,
                        self::COLUMN_KEY
                ), ['key' => $key])
        ) {

            if ($result = $statement->execute()) {
                return $result->fetchArray(SQLITE3_ASSOC);
            }
        }
        return false;
    }

    public function query(string $query): array|bool
    {

        try {
            $this->setErrorHandler();

            if ($result = $this->driver->query($query)) {
                return $result->fetchArray(SQLITE3_ASSOC);
            }
        } catch (\throwable) {

        } finally {
            restore_error_handler();
        }

        return false;
    }

}
