<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Enum\ClausePriority;

final readonly class ValuesClause implements ClauseInterface
{
    public const TYPE = ClausePriority::VALUES;

    public function __construct(
        private array $columns,
        private array $rows
    ) {
    }

    public function compileClause(): string
    {
        $cols = '(' . implode(', ', $this->columns) . ')';
        $placeholders = array_fill(0, count($this->columns), '?');
        $rowsSql = implode(
            ', ',
            array_fill(0, count($this->rows), '(' . implode(', ', $placeholders) . ')')
        );
        return "{$cols} VALUES {$rowsSql}";
    }

    public function bindings(): array
    {
        return array_merge([], ...$this->rows);
    }
}
