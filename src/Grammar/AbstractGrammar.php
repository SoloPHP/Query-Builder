<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Grammar;

use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Enum\ClausePriority;
use Solo\QueryBuilder\Identifier\TableIdentifier;

abstract class AbstractGrammar implements GrammarInterface
{
    protected string $tableQuote = '"';
    protected string $columnQuote = '"';

    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL',
        'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP', 'NOT REGEXP'
    ];

    public function wrapIdentifier(string $identifier): string
    {
        if (str_starts_with($identifier, '{') && str_ends_with($identifier, '}')) {
            return substr($identifier, 1, strlen($identifier) - 2);
        }

        if ($identifier === '*') {
            return $identifier;
        }

        if (preg_match('/^(.+?)(?:\s+as\s+|\s+)([a-z0-9_]+)$/i', $identifier, $matches)) {
            $field = trim($matches[1]);
            $alias = trim($matches[2]);

            $wrappedField = $this->wrapIdentifierWithoutAlias($field);

            return $wrappedField . ' AS ' . $this->columnQuote . $alias . $this->columnQuote;
        }

        return $this->wrapIdentifierWithoutAlias($identifier);
    }

    protected function wrapIdentifierWithoutAlias(string $identifier): string
    {
        if (str_contains($identifier, '.')) {
            $segments = explode('.', $identifier);

            if (count($segments) === 2) {
                if (trim($segments[1]) === '*') {
                    return $this->tableQuote . trim($segments[0]) . $this->tableQuote . '.*';
                }

                return $this->tableQuote . trim($segments[0]) . $this->tableQuote . '.' .
                    $this->columnQuote . trim($segments[1]) . $this->columnQuote;
            }
        }

        return $this->columnQuote . trim($identifier) . $this->columnQuote;
    }

    public function wrapTable(TableIdentifier|string $table): string
    {
        if (!($table instanceof TableIdentifier)) {
            $table = new TableIdentifier($table);
        }

        if ($table->isSubquery) {
            $wrappedTable = '(' . $table->table . ')';
            if ($table->alias) {
                $wrappedAlias = $this->tableQuote . $table->alias . $this->tableQuote;
                return $wrappedTable . ' AS ' . $wrappedAlias;
            }
            return $wrappedTable;
        }

        if ($table->alias) {
            $wrappedTable = $this->tableQuote . $table->table . $this->tableQuote;
            $wrappedAlias = $this->tableQuote . $table->alias . $this->tableQuote;
            return $wrappedTable . ' AS ' . $wrappedAlias;
        }

        return $this->tableQuote . $table->table . $this->tableQuote;
    }

    public function compileSelect(string $table, array $columns, array $clauses, bool $distinct = false): string
    {
        $cols = empty($columns) || (count($columns) === 1 && $columns[0] === '*')
            ? '*'
            : implode(', ', array_map([$this, 'wrapIdentifier'], $columns));

        $tableObj = new TableIdentifier($table);
        $tableString = $this->wrapTable($tableObj);

        $sql = "SELECT " . ($distinct ? 'DISTINCT ' : '') . "{$cols} FROM {$tableString}";
        $sql .= $this->compileClauses($clauses);

        return $sql;
    }

    public function compileInsert(string $table, array $columns, array $rows): string
    {
        $wrappedColumns = array_map([$this, 'wrapIdentifierWithoutAlias'], $columns);
        $columnsStr = implode(', ', $wrappedColumns);
        $placeholders = $this->createPlaceholders($columns, count($rows));

        $tableObj = new TableIdentifier($table);
        $tableString = $this->wrapTable($tableObj);

        return "INSERT INTO {$tableString} ({$columnsStr}) VALUES {$placeholders}";
    }

    public function compileUpdate(string $table, array $clauses): string
    {
        $tableObj = new TableIdentifier($table);
        $tableString = $this->wrapTable($tableObj);

        $sql = "UPDATE {$tableString}";

        $joinClauses = [];
        $setClauses = [];
        $whereClauses = [];

        foreach ($clauses as $clause) {
            $compiledClause = $clause->compileClause();

            if (empty($compiledClause)) {
                continue;
            }

            $clauseClass = get_class($clause);
            $clauseType = $clauseClass::TYPE;

            switch ($clauseType) {
                case ClausePriority::JOIN:
                    $joinClauses[] = $compiledClause;
                    break;
                case ClausePriority::SET:
                    $setClauses[] = $compiledClause;
                    break;
                case ClausePriority::WHERE:
                    $whereClauses[] = $compiledClause;
                    break;
            }
        }

        if (!empty($joinClauses)) {
            $sql .= ' ' . implode(' ', $joinClauses);
        }

        if (!empty($setClauses)) {
            $sql .= ' ' . implode(' ', $setClauses);
        }

        if (!empty($whereClauses)) {
            $sql .= ' ' . implode(' ', $whereClauses);
        }

        return $sql;
    }

    public function compileDelete(string $table, array $clauses): string
    {
        $tableObj = new TableIdentifier($table);
        $tableString = $this->wrapTable($tableObj);

        $sql = "DELETE FROM {$tableString}";
        $sql .= $this->compileClauses($clauses);

        return $sql;
    }

    protected function isValidOperator(string $operator): bool
    {
        return in_array(strtoupper($operator), $this->operators, true);
    }

    protected function parameterName(string $column, int $index): string
    {
        $cleanColumn = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/.*\./', '', $column));
        return ':' . $cleanColumn . '_' . $index;
    }

    protected function createPlaceholders(array $columns, int $rowCount): string
    {
        $singleRowPlaceholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        if ($rowCount === 1) {
            return $singleRowPlaceholders;
        }

        return implode(', ', array_fill(0, $rowCount, $singleRowPlaceholders));
    }

    protected function compileClauses(array $clauses): string
    {
        return !empty($clauses) ? ' ' . implode(' ', array_map(fn($clause) => $clause->compileClause(), $clauses)) : '';
    }
}
