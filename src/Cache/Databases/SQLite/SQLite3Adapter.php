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



        if ($statement = $this->prepare(sprintf(
                        'SELECT %s FROM %s WHERE %s = :key LIMIT 1',
                        implode(',', $columns),
                        $this->table,
                        $columns[0]
                ), ['key' => $key])
        ) {

            try {
                $this->setErrorHandler();

                $result = $statement->execute()->fetchArray(SQLITE3_ASSOC);

                if (false === $result) {
                    return false;
                }

                return $result;
            } catch (\Throwable) {
                return false;
            } finally { restore_error_handler(); }
        }
    }

}
