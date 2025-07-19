<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Utility;

use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Executors\PdoExecutor\PdoExecutor;
use Solo\QueryBuilder\Executors\PdoExecutor\Connection;
use Solo\QueryBuilder\Executors\PdoExecutor\Config;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;
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
}
