<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Components;

use Solo\QueryBuilder\Exceptions\QueryBuilderException;
use DateTimeImmutable;
use Closure;

final class ConditionBuilder
{
    private const VALUE_OPERATORS = ['=', '>', '<', '>=', '<=', '<>', '!=', 'LIKE', 'IN', 'BETWEEN'];
    private const NULL_OPERATORS = ['IS NULL', 'IS NOT NULL', 'IS TRUE', 'IS FALSE', 'IS NOT TRUE', 'IS NOT FALSE'];

    private array $conditions = [];
    private array $bindings = [];
    private string $alias;

    public function __construct(string $alias = '')
    {
        $this->alias = $alias;
    }

    public function where(string $field, string $operator, mixed $value = null): self
    {
        return $this->andWhere($field, $operator, $value);
    }

    public function andWhere(string $field, string $operator, mixed $value = null): self
    {
        $this->addCondition('AND', $field, $operator, $value);
        return $this;
    }

    public function orWhere(string $field, string $operator, mixed $value = null): self
    {
        $this->addCondition('OR', $field, $operator, $value);
        return $this;
    }

    public function andBetween(string $field, mixed $start, mixed $end): self
    {
        $this->addCondition('AND', $field, 'BETWEEN', [$start, $end]);
        return $this;
    }

    public function andRaw(string $sql, array $bindings = []): self
    {
        $this->conditions[] = ['AND', $sql];
        array_push($this->bindings, ...$bindings);
        return $this;
    }

    public function andGroup(Closure $callback): self
    {
        $groupBuilder = new self($this->alias);
        $callback($groupBuilder);

        $this->conditions[] = ['AND', '(' . $groupBuilder->build() . ')'];
        array_push($this->bindings, ...$groupBuilder->getBindings());
        return $this;
    }

    public function build(): string
    {
        $parts = [];
        foreach ($this->conditions as $index => [$logic, $clause]) {
            $parts[] = ($index > 0 ? $logic . ' ' : '') . $clause;
        }
        return implode(' ', $parts);
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function hasConditions(): bool
    {
        return !empty($this->conditions);
    }

    private function addCondition(string $logic, string $field, string $operator, mixed $value): void
    {
        $operator = strtoupper(trim($operator));
        $this->validateOperator($operator);

        $processed = $this->processCondition($field, $operator, $value);
        $this->conditions[] = [$logic, $processed['clause']];
        array_push($this->bindings, ...$processed['bindings']);
    }

    private function processCondition(string $field, string $operator, mixed $value): array
    {
        $field = $this->applyAliasToField($field);

        if (in_array($operator, self::NULL_OPERATORS)) {
            return ['clause' => "$field $operator", 'bindings' => []];
        }

        if ($operator === 'IN') {
            return [
                'clause' => "$field IN ?a",
                'bindings' => [(array)$value]
            ];
        }

        if ($operator === 'BETWEEN') {
            return [
                'clause' => "$field BETWEEN ?s AND ?s",
                'bindings' => array_values((array)$value),
            ];
        }

        if ($operator === 'LIKE') {
            return [
                'clause' => "$field LIKE ?l",
                'bindings' => [$value],
            ];
        }

        $placeholder = $this->detectPlaceholder($value);
        return [
            'clause' => "$field $operator $placeholder",
            'bindings' => [$value],
        ];
    }

    public function applyAlias(string $field, ?string $contextAlias = null): string
    {
        $alias = $contextAlias ?? $this->alias;
        if (!$alias || str_contains($field, '.')) {
            return $field;
        }
        return "$alias.$field";
    }

    private function detectPlaceholder(mixed $value): string
    {
        return match (true) {
            is_int($value) => '?i',
            is_float($value) => '?f',
            $value instanceof DateTimeImmutable => '?d',
            is_array($value) => '?a',
            default => '?s',
        };
    }

    private function applyAliasToField(string $field): string
    {
        if (str_contains($field, '.')) {
            return $field;
        }
        return $this->alias ? "$this->alias.$field" : $field;
    }

    private function validateOperator(string $operator): void
    {
        $allowed = array_merge(self::VALUE_OPERATORS, self::NULL_OPERATORS);
        if (!in_array($operator, $allowed)) {
            throw new QueryBuilderException("Invalid operator: $operator");
        }
    }
}