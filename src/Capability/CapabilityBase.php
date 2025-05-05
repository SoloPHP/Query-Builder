<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Enum\ClausePriority;

trait CapabilityBase
{
    abstract protected function getGrammar(): GrammarInterface;
    abstract protected function addClause(ClauseInterface $clause, ClausePriority $priority): static;
    abstract public function getBindings(): array;
}