<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Clause\LimitClause;
use Solo\QueryBuilder\Clause\OrderByClause;
use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Cache\CacheManager;
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

    public function __construct(
        string $table,
        CompilerInterface $compiler,
        ?ExecutorInterface $executor = null,
        ?CacheManager $cacheManager = null
    ) {
        parent::__construct($compiler, $executor, $cacheManager, $table);
    }

    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function build(): array
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Table name is not specified. Use "from" method to set the table.');
        }

        $clausesSql = $this->getClausesSql();
        $sql = $this->compiler->compileSelect($this->table, $this->columns, $clausesSql, $this->distinct);
        return [$sql, $this->getBindings()];
    }

    public function buildCount(?string $column = null, bool $distinct = false): array
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Table name is not specified. Use "from" method to set the table.');
        }

        $countExpression = '{';
        $countExpression .= $distinct ? 'COUNT(DISTINCT ' : 'COUNT(';
        $countExpression .= $column ? $this->getGrammar()->wrapIdentifier($column) : '*';
        $countExpression .= ') as total_count}';

        $clausesSql = $this->getFilteredClausesSql(function ($item) {
            return !(
                $item['clause'] instanceof OrderByClause ||
                $item['clause'] instanceof LimitClause
            );
        });

        $sql = $this->compiler->compileSelect($this->table, [$countExpression], $clausesSql, false);
        return [$sql, $this->getBindings()];
    }
}