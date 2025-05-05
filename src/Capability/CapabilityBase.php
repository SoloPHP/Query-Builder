<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;

trait CapabilityBase
{
    protected const PRIORITY_JOIN = 10;
    protected const PRIORITY_SET = 15;
    protected const PRIORITY_WHERE = 20;
    protected const PRIORITY_GROUP_BY = 30;
    protected const PRIORITY_HAVING = 40;
    protected const PRIORITY_ORDER_BY = 50;
    protected const PRIORITY_LIMIT = 60;

    abstract protected function getGrammar(): GrammarInterface;
    abstract protected function addClause(ClauseInterface $clause, int $priority): static;
    abstract public function getBindings(): array;
}