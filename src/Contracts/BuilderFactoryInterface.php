<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

use Solo\QueryBuilder\Builder\{
    SelectBuilder, InsertBuilder, UpdateBuilder, DeleteBuilder
};
use Solo\QueryBuilder\Cache\CacheManager;

interface BuilderFactoryInterface
{
    public function select(string $table, ?CacheManager $cacheManager = null): SelectBuilder;

    public function insert(string $table): InsertBuilder;

    public function update(string $table): UpdateBuilder;

    public function delete(string $table): DeleteBuilder;

    public function setDatabaseType(string $type): void;

    public function getDatabaseType(): string;

    public function getExecutor(): ExecutorInterface;
}