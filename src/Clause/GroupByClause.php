<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;

final readonly class GroupByClause implements ClauseInterface
{
    public function __construct(
        private array $columns,
        private ?GrammarInterface $grammar = null
    ) {
    }

    public function toSql(): string
    {
        if (empty($this->columns)) {
            return '';
        }

        $wrappedColumns = $this->columns;

        if ($this->grammar) {
            $wrappedColumns = array_map(function($column) {
                return str_starts_with($column, '{') && str_ends_with($column, '}')
                    ? substr($column, 1, -1)
                    : $this->grammar->wrapIdentifier($column);
            }, $this->columns);
        }

        return 'GROUP BY ' . implode(', ', $wrappedColumns);
    }

    public function bindings(): array
    {
        return [];
    }
}