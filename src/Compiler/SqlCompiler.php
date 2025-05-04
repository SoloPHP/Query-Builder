<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Compiler;

use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Contracts\BuilderInterface;

final readonly class SqlCompiler implements CompilerInterface
{
    public function __construct(private GrammarInterface $grammar)
    {
    }

    public function getGrammar(): GrammarInterface
    {
        return $this->grammar;
    }

    public function compile(BuilderInterface $builder): array
    {
        $sql = $builder->toSql();
        $bindings = $builder->getBindings();
        return [$sql, $bindings];
    }

    public function compileSelect(string $table, array $columns, array $clauses, bool $distinct = false): string
    {
        return $this->grammar->compileSelect($table, $columns, $clauses, $distinct);
    }

    public function compileInsert(string $table, array $columns, array $rows): string
    {
        return $this->grammar->compileInsert($table, $columns, $rows);
    }

    public function compileUpdate(string $table, array $assignments, array $clauses): string
    {
        return $this->grammar->compileUpdate($table, $assignments, $clauses);
    }

    public function compileDelete(string $table, array $clauses): string
    {
        return $this->grammar->compileDelete($table, $clauses);
    }
}