<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

use Solo\QueryBuilder\Identifier\TableIdentifier;

interface GrammarInterface
{
    public function wrapIdentifier(string $identifier): string;

    public function wrapTable(string|TableIdentifier $table): string;

    public function compileSelect(string $table, array $columns, array $clauses, bool $distinct = false): string;

    public function compileInsert(string $table, array $columns, array $rows): string;

    public function compileUpdate(string $table, array $assignments, array $clauses): string;

    public function compileDelete(string $table, array $clauses): string;
}