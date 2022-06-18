<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Databases;

/**
 * Basic CRUD operations
 */
interface DatabaseAdapter
{

    public const COLUMN_KEY = 'id';
    public const COLUMN_DATA = 'data';
    public const COLUMN_EXPIRY = 'expiry';
    public const COLUMN_TAGS = 'tags';

    public function createTable(string $table): bool;

    public function read(string $key, bool $data = true): array|false;

    public function write(array $data): bool;

    public function delete(string $key): bool;

    public function purge(): bool;

    public function clear(): bool;
}
