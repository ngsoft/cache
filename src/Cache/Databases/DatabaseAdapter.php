<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases;

/**
 * Basic CRUD operations
 */
interface DatabaseAdapter
{

    public function createTable(string $table): bool;

    public function read(string $key, bool $data = true): array|false;

    public function write(string $key, array $data): bool;

    public function delete(string $key): bool;
}
