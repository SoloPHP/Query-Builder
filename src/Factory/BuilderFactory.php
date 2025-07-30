<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Factory;

use Solo\QueryBuilder\Contracts\BuilderFactoryInterface;
use Solo\QueryBuilder\Contracts\GrammarFactoryInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Cache\CacheManager;
use Solo\QueryBuilder\Compiler\SqlCompiler;
use Solo\QueryBuilder\Builder\{
    SelectBuilder,
    InsertBuilder,
    UpdateBuilder,
    DeleteBuilder
};

final class BuilderFactory implements BuilderFactoryInterface
{
    private string $defaultDatabaseType;
    private GrammarFactoryInterface $grammarFactory;
    private ExecutorInterface $executor;

    public function __construct(
        GrammarFactoryInterface $grammarFactory,
        ExecutorInterface $executor,
        string $defaultDatabaseType = 'mysql'
    ) {
        $this->grammarFactory = $grammarFactory;
        $this->executor = $executor;
        $this->defaultDatabaseType = $defaultDatabaseType;
    }

    public function setDatabaseType(string $type): void
    {
        $this->defaultDatabaseType = $type;
    }

    public function getDatabaseType(): string
    {
        return $this->defaultDatabaseType;
    }

    public function getExecutor(): ExecutorInterface
    {
        return $this->executor;
    }

    public function select(string $table, ?CacheManager $cacheManager = null): SelectBuilder
    {
        $grammar = $this->grammarFactory->create($this->defaultDatabaseType);
        $compiler = new SqlCompiler($grammar);
        return new SelectBuilder($table, $compiler, $this->executor, $cacheManager);
    }

    public function insert(string $table): InsertBuilder
    {
        $grammar = $this->grammarFactory->create($this->defaultDatabaseType);
        $compiler = new SqlCompiler($grammar);
        return new InsertBuilder($table, $compiler, $this->executor);
    }

    public function update(string $table): UpdateBuilder
    {
        $grammar = $this->grammarFactory->create($this->defaultDatabaseType);
        $compiler = new SqlCompiler($grammar);
        return new UpdateBuilder($table, $compiler, $this->executor);
    }

    public function delete(string $table): DeleteBuilder
    {
        $grammar = $this->grammarFactory->create($this->defaultDatabaseType);
        $compiler = new SqlCompiler($grammar);
        return new DeleteBuilder($table, $compiler, $this->executor);
    }
}
