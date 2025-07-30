<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Enum\ClausePriority;
use Solo\QueryBuilder\Identifier\TableIdentifier;

final readonly class JoinClause implements ClauseInterface
{
    public const TYPE = ClausePriority::JOIN;

    public function __construct(
        private string $type,
        private TableIdentifier $table,
        private string $on,
        private array $bindings = [],
        private ?GrammarInterface $grammar = null
    ) {
    }

    public function compileClause(): string
    {
        $tableString = $this->grammar->wrapTable($this->table);
        $onCondition = $this->on;

        if ($this->grammar) {
            $pattern = '/\b([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\b/';

            $onCondition = preg_replace_callback($pattern, function ($matches) {
                return $this->grammar->wrapIdentifier($matches[0]);
            }, $onCondition);
        }

        return sprintf('%s JOIN %s ON %s', $this->type, $tableString, $onCondition);
    }

    public function bindings(): array
    {
        return $this->bindings;
    }
}
