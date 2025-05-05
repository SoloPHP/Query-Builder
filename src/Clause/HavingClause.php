<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Clause;

use Solo\QueryBuilder\Condition\ConditionBuilder;
use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Enum\ClausePriority;

final readonly class HavingClause implements ClauseInterface
{
    public const TYPE = ClausePriority::HAVING;

    public function __construct(
        private ConditionBuilder $cb,
        private ?GrammarInterface $grammar = null
    ) {
        if ($this->grammar) {
            $this->cb->setGrammar($this->grammar);
        }
    }

    public function compileClause(): string
    {
        $sql = $this->cb->toSql();
        return $sql !== '' ? 'HAVING ' . $sql : '';
    }

    public function bindings(): array
    {
        return $this->cb->bindings();
    }
}