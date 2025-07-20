<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Pool;

use Solo\QueryBuilder\Executors\PdoExecutor\Connection;

interface ConnectionPoolInterface
{
    public function getConnection(): Connection;

    public function releaseConnection(Connection $connection): void;

    public function closeAll(): void;

    public function getActiveConnections(): int;

    public function getIdleConnections(): int;

    public function getTotalConnections(): int;
}