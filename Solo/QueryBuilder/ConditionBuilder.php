<?php

namespace Solo\QueryBuilder;

use Closure;
use InvalidArgumentException;

class ConditionBuilder
{
    protected array $conditions = [];
    protected array $bindings = [];
    private string $alias;

    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function applyAlias(string $field): string
    {
        return str_contains($field, '.') ? $field : "{$this->alias}.$field";
    }

    protected function addCondition(string $logic, string $expression, array $bindings = []): void
    {
        $this->conditions[] = [
            'logic' => $logic,
            'expression' => $expression,
        ];
        $this->bindings = array_merge($this->bindings, $bindings);
    }

    public function whereRaw(string $sql, array $bindings = [], string $logic = 'AND'): self
    {
        $this->addCondition($logic, $sql, $bindings);
        return $this;
    }

    public function where(string $field, string $operator, mixed $value, string $logic = 'AND'): self
    {
        $field = $this->applyAlias($field);
        $operator = $this->validateOperator($operator);
        $placeholder = $this->resolvePlaceholder($value);

        $this->addCondition(
            $logic,
            "$field $operator $placeholder",
            (array)$value
        );
        return $this;
    }

    public function whereIn(string $field, array $values, string $logic = 'AND'): self
    {
        if (empty($values)) {
            throw new InvalidArgumentException("Values for IN condition cannot be empty.");
        }

        $field = $this->applyAlias($field);
        $this->addCondition(
            $logic,
            "$field IN (?a)",
            $values
        );
        return $this;
    }

    public function whereNull(string $field, string $logic = 'AND'): self
    {
        $field = $this->applyAlias($field);
        $this->addCondition($logic, "$field IS NULL");
        return $this;
    }

    public function whereNotNull(string $field, string $logic = 'AND'): self
    {
        $field = $this->applyAlias($field);
        $this->addCondition($logic, "$field IS NOT NULL");
        return $this;
    }

    public function whereLike(string $field, string $pattern, string $logic = 'AND'): self
    {
        $field = $this->applyAlias($field);
        $this->addCondition(
            $logic,
            "$field LIKE ?s",
            [$pattern]
        );
        return $this;
    }

    public function whereBetween(string $field, mixed $start, mixed $end, string $logic = 'AND'): self
    {
        $field = $this->applyAlias($field);
        $this->addCondition(
            $logic,
            "$field BETWEEN ?s AND ?s",
            [$start, $end]
        );
        return $this;
    }

    public function whereGroup(Closure $callback, string $logic = 'AND'): self
    {
        $nestedBuilder = new self($this->alias);
        $callback($nestedBuilder);

        $this->addCondition(
            $logic,
            "(" . $nestedBuilder->buildConditions() . ")",
            $nestedBuilder->getBindings()
        );
        return $this;
    }

    public function buildConditions(): string
    {
        $parts = [];
        foreach ($this->conditions as $index => $condition) {
            $prefix = $index === 0 ? '' : $condition['logic'] . ' ';
            $parts[] = $prefix . $condition['expression'];
        }
        return implode(' ', $parts);
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function reset(): void
    {
        $this->conditions = [];
        $this->bindings = [];
    }

    private function validateOperator(string $operator): string
    {
        $allowed = ['=', '>', '<', '>=', '<=', '<>', '!=', 'LIKE'];
        $operatorUpper = strtoupper($operator);

        if (!in_array($operatorUpper, $allowed)) {
            throw new InvalidArgumentException("Invalid operator: $operator");
        }

        return $operatorUpper;
    }

    private function resolvePlaceholder(mixed $value): string
    {
        return match (true) {
            is_array($value) => '?a',
            is_int($value) => '?i',
            default => '?s',
        };
    }
}