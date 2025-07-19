<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

interface CompilerInterface
{
    public function getGrammar(): GrammarInterface;

    public function compile(BuilderInterface $builder): array;

    public function compileSelect(string $table, array $columns, array $clauses, bool $distinct = false): string;

    public function compileInsert(string $table, array $columns, array $rows): string;

    public function compileUpdate(string $table, array $clauses): string;

    public function compileDelete(string $table, array $clauses): string;
}
