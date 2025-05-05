<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Clause\LimitClause;
use Solo\QueryBuilder\Clause\OrderByClause;
use Solo\QueryBuilder\Contracts\Capability\{
    WhereCapable,
    JoinCapable,
    GroupByCapable,
    HavingCapable,
    OrderByCapable,
    LimitCapable,
    SelectionCapable,
    ResultCapable
};
use Solo\QueryBuilder\Capability\{
    WhereTrait,
    JoinTrait,
    GroupByTrait,
    HavingTrait,
    OrderByTrait,
    LimitTrait,
    SelectionTrait,
    ResultTrait
};

class SelectBuilder extends AbstractBuilder implements
    WhereCapable,
    JoinCapable,
    GroupByCapable,
    HavingCapable,
    OrderByCapable,
    LimitCapable,
    SelectionCapable,
    ResultCapable
{
    use WhereTrait;
    use JoinTrait;
    use GroupByTrait;
    use HavingTrait;
    use OrderByTrait;
    use LimitTrait;
    use SelectionTrait;
    use ResultTrait;

    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    protected function getBuilderType(): string
    {
        return 'Select';
    }

    public function buildCount(?string $column = null, bool $distinct = false): array
    {
        $this->validateTableName();

        $countExpression = '{';
        $countExpression .= $distinct ? 'COUNT(DISTINCT ' : 'COUNT(';
        $countExpression .= $column ? $this->getGrammar()->wrapIdentifier($column) : '*';
        $countExpression .= ') as total_count}';

        $clausesSql = $this->getClausesSql(function ($item) {
            return !(
                $item['clause'] instanceof OrderByClause ||
                $item['clause'] instanceof LimitClause
            );
        });

        $sql = $this->compiler->compileSelect($this->table, [$countExpression], $clausesSql, false);
        return [$sql, $this->getBindings()];
    }
}