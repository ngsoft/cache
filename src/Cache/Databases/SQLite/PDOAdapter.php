<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases\SQLite;

use NGSOFT\Cache\Databases\DatabaseAdapter;

class PDOAdapter extends QueryEngine implements DatabaseAdapter
{

    public function __construct(
            protected \PDO $driver
    )
    {

    }

    public function read(string $key, bool $data = true): array|false
    {

    }

}
