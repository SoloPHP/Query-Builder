<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Enum\ClausePriority;
use Solo\QueryBuilder\Utility\Raw;

final readonly class GroupByClause implements ClauseInterface
{
    public const TYPE = ClausePriority::GROUP_BY;

    public function __construct(
        private array $columns,
        private ?GrammarInterface $grammar = null
    ) {
    }

    public function compileClause(): string
    {
        if (empty($this->columns)) {
            return '';
        }

        $wrappedColumns = $this->columns;

        if ($this->grammar) {
            $wrappedColumns = array_map(function($column) {
                return Raw::is($column)
                    ? Raw::get($column)
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