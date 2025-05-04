<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\JoinClause;
use Solo\QueryBuilder\Identifier\TableIdentifier;

trait JoinTrait
{
    use CapabilityBase;

    public function join(string $table, string $condition, mixed ...$bindings): static
    {
        $tid = new TableIdentifier($table);
        return $this->addClause(
            new JoinClause('INNER', $tid, $condition, $bindings, $this->getGrammar()),
            static::PRIORITY_JOIN
        );
    }

    public function leftJoin(string $table, string $condition, mixed ...$bindings): static
    {
        $tid = new TableIdentifier($table);
        return $this->addClause(
            new JoinClause('LEFT', $tid, $condition, $bindings, $this->getGrammar()),
            static::PRIORITY_JOIN
        );
    }

    public function rightJoin(string $table, string $condition, mixed ...$bindings): static
    {
        $tid = new TableIdentifier($table);
        return $this->addClause(
            new JoinClause('RIGHT', $tid, $condition, $bindings, $this->getGrammar()),
            static::PRIORITY_JOIN
        );
    }

    public function fullJoin(string $table, string $condition, mixed ...$bindings): static
    {
        $tid = new TableIdentifier($table);
        return $this->addClause(
            new JoinClause('FULL OUTER', $tid, $condition, $bindings, $this->getGrammar()),
            static::PRIORITY_JOIN
        );
    }

    public function joinSub(\Closure $callback, string $alias, string $condition, mixed ...$bindings): static
    {
        $className = $this::class; // Get the current class name
        $subBuilder = new $className('', $this->compiler);
        $callback($subBuilder);

        [$subSql, $subBindings] = $subBuilder->build();

        $subQueryTable = new TableIdentifier($subSql, $alias, true);

        $allBindings = array_merge($subBindings, $bindings);

        return $this->addClause(
            new JoinClause('INNER', $subQueryTable, $condition, $allBindings, $this->getGrammar()),
            static::PRIORITY_JOIN
        );
    }
}