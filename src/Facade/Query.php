<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Facade;

use Psr\SimpleCache\CacheInterface;
use Solo\QueryBuilder\Cache\CacheManager;
use Solo\QueryBuilder\Contracts\BuilderFactoryInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Executors\PdoExecutor\PooledExecutor;
use Solo\QueryBuilder\Pool\ConnectionPoolInterface;
use Solo\QueryBuilder\Builder\{
    SelectBuilder,
    InsertBuilder,
    UpdateBuilder,
    DeleteBuilder
};

final class Query
{
    private BuilderFactoryInterface $builderFactory;
    private ExecutorInterface $executor;
    private ?CacheManager $cacheManager = null;

    private static ?CacheManager $defaultCacheManager = null;

    public function __construct(BuilderFactoryInterface $builderFactory)
    {
        $this->builderFactory = $builderFactory;
        $this->executor = $builderFactory->getExecutor();
    }

    public function withCache(CacheInterface $cache, ?int $ttl = null): self
    {
        $this->cacheManager = new CacheManager($cache, $ttl);
        return $this;
    }

    public static function enableCache(CacheInterface $cache, ?int $ttl = null): void
    {
        self::$defaultCacheManager = new CacheManager($cache, $ttl);
    }

    public static function disableCache(): void
    {
        self::$defaultCacheManager = null;
    }

    public function select(string ...$columns): SelectBuilder
    {
        $builder = $this->builderFactory->select('', $this->cacheManager ?? self::$defaultCacheManager);

        if (!empty($columns)) {
            $builder->select(...$columns);
        }

        return $builder;
    }

    public function from(string $table): SelectBuilder
    {
        return $this->builderFactory->select($table, $this->cacheManager ?? self::$defaultCacheManager);
    }

    public function insert(string $table): InsertBuilder
    {
        return $this->builderFactory->insert($table);
    }

    public function update(string $table): UpdateBuilder
    {
        return $this->builderFactory->update($table);
    }

    public function delete(string $table): DeleteBuilder
    {
        return $this->builderFactory->delete($table);
    }

    public function setDatabaseType(string $type): void
    {
        $this->builderFactory->setDatabaseType($type);
    }

    public function beginTransaction(): bool
    {
        return $this->executor->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->executor->commit();
    }

    public function inTransaction(): bool
    {
        return $this->executor->inTransaction();
    }

    public function rollBack(): bool
    {
        return $this->executor->rollBack();
    }

    public function getExecutor(): ExecutorInterface
    {
        return $this->executor;
    }

    public function getConnectionPool(): ?ConnectionPoolInterface
    {
        if ($this->executor instanceof PooledExecutor) {
            return $this->executor->getConnectionPool();
        }
        return null;
    }
}
