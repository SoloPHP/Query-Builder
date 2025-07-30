<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Utility;

use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Executors\PdoExecutor\{PdoExecutor, Connection, Config, PooledExecutor};
use Solo\QueryBuilder\Pool\ConnectionPool;
use Solo\QueryBuilder\Factory\{BuilderFactory, GrammarFactory};
use Psr\SimpleCache\CacheInterface;
use PDO;

final class QueryFactory
{
    public static function createWithPdo(
        string $host,
        string $username,
        string $password,
        string $database,
        int $fetchMode = PDO::FETCH_ASSOC,
        string $dbType = 'mysql',
        ?int $port = null,
        array $options = [],
        ?CacheInterface $cache = null,
        ?int $cacheTtl = null
    ): Query {
        $grammarFactory = new GrammarFactory();

        $config = new Config($host, $username, $password, $database, $fetchMode, $dbType, $port, $options);
        $connection = new Connection($config);
        $executor = new PdoExecutor($connection);

        $builderFactory = new BuilderFactory($grammarFactory, $executor, $dbType);

        $query = new Query($builderFactory);

        if ($cache !== null) {
            $query->withCache($cache, $cacheTtl);
        }

        return $query;
    }

    public static function createWithConnectionPool(
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
            $config,
            $maxConnections,
            $minConnections,
            $maxIdleTime,
            $connectionTimeout
        );

        $executor = new PooledExecutor($pool, $fetchMode);

        $builderFactory = new BuilderFactory($grammarFactory, $executor, $dbType);

        $query = new Query($builderFactory);

        if ($cache !== null) {
            $query->withCache($cache, $cacheTtl);
        }

        return $query;
    }
}
