<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases\SQLite;

class SQLite3Adapter extends QueryEngine implements \NGSOFT\Cache\Databases\DatabaseAdapter
{

    public function __construct(
            protected \SQLite3 $driver
    )
    {

    }

    public function read(string $key, bool $data = true): array|false
    {

    }

}
