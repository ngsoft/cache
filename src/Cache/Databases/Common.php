<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases;

abstract class Common
{

    /**
     * @param string $query
     * @param array $bindings
     * @return \PDOStatement|\SQLite3Stmt|false
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
        } catch (Throwable $err) {
            return false;
        } finally { \restore_error_handler(); }
    }

}
