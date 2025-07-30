<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Utility;

use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Executors\PdoExecutor\{Config, PooledExecutor};
use Solo\QueryBuilder\Pool\{ConnectionPool, ConnectionPoolInterface};
use Solo\QueryBuilder\Factory\{BuilderFactory, GrammarFactory};
use Psr\SimpleCache\CacheInterface;
use PDO;

final class PooledQueryFactory
{
    public static function createWithPool(
        string $host,
        string $username,
        string $password,
        string $database,
        int $fetchMode = PDO::FETCH_ASSOC,
        string $dbType = 'mysql',
        ?int $port = null,
        array $options = [],
        ?CacheInterface $cache = null,
        ?int $cacheTtl = null,
        int $maxConnections = 10,
        int $minConnections = 2,
        int $maxIdleTime = 3600,
        int $connectionTimeout = 30
    ): Query {
        $grammarFactory = new GrammarFactory();

        $config = new Config($host, $username, $password, $database, $fetchMode, $dbType, $port, $options);

        $pool = new ConnectionPool(
            config: $config,
            maxConnections: $maxConnections,
            minConnections: $minConnections,
            maxIdleTime: $maxIdleTime,
            connectionTimeout: $connectionTimeout
        );

        $executor = new PooledExecutor($pool, $fetchMode);

        $builderFactory = new BuilderFactory($grammarFactory, $executor, $dbType);

        $query = new Query($builderFactory);

        if ($cache !== null) {
            $query->withCache($cache, $cacheTtl);
        }

        return $query;
    }

    public static function createPool(
        Config $config,
        int $maxConnections = 10,
        int $minConnections = 2,
        int $maxIdleTime = 3600,
        int $connectionTimeout = 30
    ): ConnectionPoolInterface {
        return new ConnectionPool(
            config: $config,
            maxConnections: $maxConnections,
            minConnections: $minConnections,
            maxIdleTime: $maxIdleTime,
            connectionTimeout: $connectionTimeout
        );
    }
}
