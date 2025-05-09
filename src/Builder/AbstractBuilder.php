<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Contracts\BuilderInterface;
use Solo\QueryBuilder\Contracts\ClauseInterface;
use Solo\QueryBuilder\Contracts\CompilerInterface;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Exception\InvalidTableException;
use Solo\QueryBuilder\Enum\ClausePriority;

abstract class AbstractBuilder implements BuilderInterface
{
    public const TYPE = '';

    protected string $table = '';
    protected array $bindings = [];

    /** @var array<array{0: int, 1: ClauseInterface}> */
    protected array $clauses = [];

    public function __construct(
        string $table,
        protected readonly CompilerInterface $compiler,
        protected ?ExecutorInterface $executor = null
    ) {
            $this->table = $table;
        }

    protected function validateTableName(): void
    {
        if (empty($this->table)) {
            throw new InvalidTableException('Table name is not specified.');
        }
    }

    protected function validateExecutor(): void
    {
        if (!$this->executor) {
            throw new \RuntimeException('No executor available to execute the query');
        }
    }

    public function build(): array
    {
        $this->validateTableName();
        return $this->doBuild();
    }

    abstract protected function doBuild(): array;

    final protected function getGrammar(): GrammarInterface
    {
        return $this->compiler->getGrammar();
    }

    protected function addClause(ClauseInterface $clause, ClausePriority $priority): static
    {
        $this->clauses[] = [$priority->value, $clause];
        return $this;
    }

    protected function addBindings(array $bindings): static
    {
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    protected function filterClauses(string $clauseClassName): void
    {
        $this->clauses = array_filter($this->clauses, function ($item) use ($clauseClassName) {
            return !($item[1] instanceof $clauseClassName);
        });
    }

    protected function getClauseObjects(): array
    {
        usort($this->clauses, static fn($a, $b) => $a[0] <=> $b[0]);
        return array_map(fn($item) => $item[1], $this->clauses);
    }

    public function toSql(): string
    {
        [$sql, ] = $this->build();
        return $sql;
    }

    public function getBindings(): array
    {
        $all = $this->bindings;
        foreach ($this->clauses as $item) {
            $all = array_merge($all, $item[1]->bindings());
        }
        return $all;
    }
}