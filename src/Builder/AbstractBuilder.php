<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Contracts\BuilderInterface;
use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Cache\CacheManager;
use Solo\QueryBuilder\Capability\WhenTrait;
use Solo\QueryBuilder\Contracts\Capability\WhenCapable;

abstract class AbstractBuilder implements BuilderInterface, WhenCapable
{
    use WhenTrait;

    protected array $clauses = [];
    protected string $table = '';
    protected array $bindings = [];

    public function __construct(
        string $table = '',
        protected readonly CompilerInterface $compiler,
        protected ?ExecutorInterface $executor = null,
        protected ?CacheManager $cacheManager = null
    ) {
            $this->table = $table;
        }

    protected function validateTableName(): void
    {
        if (empty($this->table)) {
            throw new \InvalidArgumentException('Table name is not specified.');
        }
    }

    public function build(): array
    {
        $this->validateTableName();
        return $this->doBuild();
    }

    protected function doBuild(): array
    {
        $clausesSql = $this->getClausesSql();
        $method = 'compile' . $this->getBuilderType();

        $bindings = $this->getBindings();

        if ($this->getBuilderType() === 'Select') {
            $columns = $this->columns ?? ['*'];
            $distinct = $this->distinct ?? false;
            $sql = $this->compiler->$method($this->table, $columns, $clausesSql, $distinct);
        } elseif ($this->getBuilderType() === 'Insert') {
            $sql = $this->compiler->$method($this->table, [], []);
        } else {
            $sql = $this->compiler->$method($this->table, $clausesSql);
        }

        return [$sql, $bindings];
    }

    abstract protected function getBuilderType(): string;

    protected function getGrammar(): GrammarInterface
    {
        return $this->compiler->getGrammar();
    }

    protected function addClause(ClauseInterface $clause, int $priority): static
    {
        $this->clauses[] = [
            'clause' => $clause,
            'priority' => $priority
        ];
        return $this;
    }

    protected function addBindings(array $bindings): static
    {
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    protected function sortClauses(): void
    {
        usort($this->clauses, fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    protected function compileClauses(): string
    {
        $this->sortClauses();

        $pieces = array_map(
            fn($item): string => $item['clause']->compileClause(),
            $this->clauses
        );
        return trim(implode(' ', $pieces));
    }

    protected function getClausesSql(?callable $filter = null): array
    {
        $filteredClauses = $filter
            ? array_filter($this->clauses, $filter)
            : $this->clauses;

        usort($filteredClauses, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return array_map(fn($item) => $item['clause']->compileClause(), $filteredClauses);
    }

    protected function isRawExpression(string $value): bool
    {
        return str_starts_with($value, '{') && str_ends_with($value, '}');
    }

    protected function getRawContent(string $value): string
    {
        if ($this->isRawExpression($value)) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    public function toSql(): string
    {
        [$sql, $bindings] = $this->build();
        return $sql;
    }

    public function getBindings(): array
    {
        $all = $this->bindings;
        foreach ($this->clauses as $item) {
            $all = array_merge($all, $item['clause']->bindings());
        }
        return $all;
    }
}