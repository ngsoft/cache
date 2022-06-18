<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases\SQLite;

use NGSOFT\Cache\Databases\Common;

abstract class QueryEngine extends Common
{

    public function createTable(string $table): bool
    {

    }

    public function delete(string $key): bool
    {

    }

    public function write(string $key, array $data): bool
    {

    }

}
