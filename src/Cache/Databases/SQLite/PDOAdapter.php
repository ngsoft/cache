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

            if ($statement->execute()) {
                return $statement->fetch(PDO::FETCH_ASSOC);
            }
        }

        return false;
    }

}
