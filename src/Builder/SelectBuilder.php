<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Capability\{
    WhereTrait,
    JoinTrait,
    GroupByTrait,
    HavingTrait,
    OrderByTrait,
    LimitTrait,
    SelectionTrait,
    ResultTrait,
    WhenTrait
};
use Solo\QueryBuilder\Contracts\Capability\{
    WhereCapable,
    JoinCapable,
    GroupByCapable,
    HavingCapable,
    OrderByCapable,
    LimitCapable,
    SelectionCapable,
    ResultCapable,
    WhenCapable
};
use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Cache\CacheManager;

class SelectBuilder extends AbstractBuilder implements
    WhereCapable,
    JoinCapable,
    GroupByCapable,
    HavingCapable,
    OrderByCapable,
    LimitCapable,
    SelectionCapable,
    ResultCapable,
    WhenCapable
{
    public const TYPE = 'Select';

    use WhereTrait;
    use JoinTrait;
    use GroupByTrait;
    use HavingTrait;
    use OrderByTrait;
    use LimitTrait;
    use SelectionTrait;
    use ResultTrait;
    use WhenTrait;

    protected ?CacheManager $cacheManager = null;

    public function __construct(
        string $table,
        CompilerInterface $compiler,
        ?ExecutorInterface $executor = null,
        ?CacheManager $cacheManager = null
    ) {
        parent::__construct($table, $compiler, $executor);
        $this->cacheManager = $cacheManager;
    }

    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    protected function doBuild(): array
    {
        $clauseObjects = $this->getClauseObjects();
        $sql = $this->compiler->compileSelect($this->table, $this->columns, $clauseObjects, $this->distinct);
        return [$sql, $this->getBindings()];
    }

    public function buildCount(?string $column = null, bool $distinct = false): array
    {
        $this->validateTableName();

        $countExpression = $this->buildCountExpression($column, $distinct);

        $countClauses = array_filter($this->clauses, function ($item) {
            $clauseClass = get_class($item[1]);
            return !str_ends_with($clauseClass, 'OrderByClause') &&
                   !str_ends_with($clauseClass, 'LimitClause');
        });

        $clauseObjects = array_map(fn($item) => $item[1], $countClauses);

        $sql = $this->compiler->compileSelect($this->table, [$countExpression], $clauseObjects, false);
        return [$sql, $this->getBindings()];
    }

    private function buildCountExpression(?string $column, bool $distinct): string
    {
        $countExpression = '{';
        $countExpression .= $distinct ? 'COUNT(DISTINCT ' : 'COUNT(';
        $countExpression .= $column ? $this->getGrammar()->wrapIdentifier($column) : '*';
        $countExpression .= ') as total_count}';
        return $countExpression;
    }
}