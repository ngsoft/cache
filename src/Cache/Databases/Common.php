<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases;

use NGSOFT\Cache\Utils\Toolkit,
    PDO,
    PDOStatement,
    SQLite3,
    SQLite3Stmt,
    Throwable;
use function str_starts_with;

abstract class Common implements DatabaseAdapter
{

    use Toolkit;

    /**
     * @var PDO|SQLite3
     */
    protected $driver;
    protected string $table;

    /**
     * @param string $query
     * @param array $bindings
     * @return PDOStatement|SQLite3Stmt|false
     */
    protected function prepare(string $query, array $bindings = [])
    {
        try {
            $this->setErrorHandler();

            $prepared = $this->driver->prepare($query);
            foreach ($bindings as $index => $value) {
                if (is_string($index) && ! str_starts_with($index, ':')) {
                    $index = ":$index";
                }
                if (is_int($index)) $index ++;
                $prepared->bindValue($index, $value);
            }

            return $prepared;
        } catch (Throwable) {
            return false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return \SQLite3Result|bool
     */
    protected function execute(SQLite3Stmt|PDOStatement|false $statement)
    {
        try {
            $this->setErrorHandler();

            return $statement ? $statement->execute() : false;
        } catch (\Throwable) {
            return false;
        } finally {
            restore_error_handler();
        }
    }

    public function count(): int
    {
        $result = $this->query(sprintf('SELECT COUNT(*) as count FROM %s', $this->table));
        return $result ? $result['count'] : 0;
    }

    public function getColumns(): array
    {
        static $columns;
        $columns = $columns ?? [
            static::COLUMN_KEY,
            static::COLUMN_DATA,
            static::COLUMN_EXPIRY,
            static::COLUMN_TAGS,
        ];

        return $columns;
    }

}
