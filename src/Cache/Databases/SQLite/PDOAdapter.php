<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases\SQLite;

use PDO;

class PDOAdapter extends QueryEngine
{

    public function __construct(
            PDO $driver,
            string $table
    )
    {
        $driver->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
                        'SELECT %s FROM %s WHERE %s = ? LIMIT 1',
                        implode(',', $columns),
                        $this->table,
                        self::COLUMN_KEY
                ), [$key])
        ) {


            $this->setErrorHandler();

            if ($this->execute($statement)) {
                return $statement->fetch(PDO::FETCH_ASSOC);
            }
        }

        return false;
    }

    public function query(string $query): array|bool
    {


        try {
            $this->setErrorHandler();

            if ($result = $this->driver->query($query)) {
                return $result->fetch(PDO::FETCH_ASSOC);
            }
        } catch (\throwable) {

        } finally {
            restore_error_handler();
        }

        return false;
    }

}
