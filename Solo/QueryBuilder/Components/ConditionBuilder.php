<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Components;

use Solo\QueryBuilder\Exceptions\QueryBuilderException;
use DateTimeImmutable;
use Closure;

final class ConditionBuilder
{
    private const VALUE_OPERATORS = ['=', '>', '<', '>=', '<=', '<>', '!=', 'LIKE', 'IN', 'BETWEEN'];
    private const NULL_OPERATORS = ['IS NULL', 'IS NOT NULL', 'IS TRUE', 'IS FALSE', 'IS NOT TRUE', 'IS NOT FALSE'];

    private string $alias;
    private array $whereConditions = [];
    private array $whereBindings = [];
    private array $havingConditions = [];
    private array $havingBindings = [];

    public function __construct(string $alias = '')
    {
        $this->alias = $alias;
    }

    /*------------------------------------------------------------------------*
     *                     WHERE-section
     *------------------------------------------------------------------------*/

    public function where(string $field, string $operator, mixed $value = null): self
    {
        return $this->andWhere($field, $operator, $value);
    }

    public function andWhere(string $field, string $operator, mixed $value = null): self
    {
        $this->addWhereCondition('AND', $field, $operator, $value);
        return $this;
    }

    public function orWhere(string $field, string $operator, mixed $value = null): self
    {
        $this->addWhereCondition('OR', $field, $operator, $value);
        return $this;
    }

    public function andBetween(string $field, mixed $start, mixed $end): self
    {
        $this->addWhereCondition('AND', $field, 'BETWEEN', [$start, $end]);
        return $this;
    }

    public function andRaw(string $sql, array $bindings = []): self
    {
        $this->whereConditions[] = ['AND', $sql];
        array_push($this->whereBindings, ...$bindings);
        return $this;
    }

    public function andGroup(Closure $callback): self
    {
        $groupBuilder = new self($this->alias);
        $callback($groupBuilder);

        $groupSql = $groupBuilder->buildWhere();
        if ($groupSql !== '') {
            $this->whereConditions[] = ['AND', '(' . $groupSql . ')'];
            array_push($this->whereBindings, ...$groupBuilder->getWhereBindings());
        }

        return $this;
    }

    public function buildWhere(): string
    {
        return $this->buildGeneric($this->whereConditions);
    }

    public function getWhereBindings(): array
    {
        return $this->whereBindings;
    }

    public function hasWhereConditions(): bool
    {
        return !empty($this->whereConditions);
    }

    /*------------------------------------------------------------------------*
     *                     HAVING-section
     *------------------------------------------------------------------------*/

    public function having(string $field, string $operator, mixed $value = null): self
    {
        return $this->andHaving($field, $operator, $value);
    }

    public function andHaving(string $field, string $operator, mixed $value = null): self
    {
        $this->addHavingCondition('AND', $field, $operator, $value);
        return $this;
    }

    public function orHaving(string $field, string $operator, mixed $value = null): self
    {
        $this->addHavingCondition('OR', $field, $operator, $value);
        return $this;
    }

    public function andHavingBetween(string $field, mixed $start, mixed $end): self
    {
        $this->addHavingCondition('AND', $field, 'BETWEEN', [$start, $end]);
        return $this;
    }

    public function andHavingRaw(string $sql, array $bindings = []): self
    {
        $this->havingConditions[] = ['AND', $sql];
        array_push($this->havingBindings, ...$bindings);
        return $this;
    }

    public function andHavingGroup(Closure $callback): self
    {
        $groupBuilder = new self($this->alias);
        $callback($groupBuilder);

        $groupSql = $groupBuilder->buildHaving();
        if ($groupSql !== '') {
            $this->havingConditions[] = ['AND', '(' . $groupSql . ')'];
            array_push($this->havingBindings, ...$groupBuilder->getHavingBindings());
        }

        return $this;
    }

    public function buildHaving(): string
    {
        return $this->buildGeneric($this->havingConditions);
    }

    public function getHavingBindings(): array
    {
        return $this->havingBindings;
    }

    public function hasHavingConditions(): bool
    {
        return !empty($this->havingConditions);
    }

    /*------------------------------------------------------------------------*
    *                     Helper methods
    *------------------------------------------------------------------------*/

    private function buildGeneric(array $conditions): string
    {
        if (empty($conditions)) {
            return '';
        }

        $parts = [];
        foreach ($conditions as $index => [$logic, $clause]) {
            $prefix = ($index > 0) ? $logic . ' ' : '';
            $parts[] = $prefix . $clause;
        }

        return implode(' ', $parts);
    }

    private function addWhereCondition(string $logic, string $field, string $operator, mixed $value): void
    {
        $operator = strtoupper(trim($operator));
        $this->validateOperator($operator);

        $processed = $this->processCondition($field, $operator, $value);
        $this->whereConditions[] = [$logic, $processed['clause']];
        array_push($this->whereBindings, ...$processed['bindings']);
    }

    private function addHavingCondition(string $logic, string $field, string $operator, mixed $value): void
    {
        $operator = strtoupper(trim($operator));
        $this->validateOperator($operator);

        $processed = $this->processCondition($field, $operator, $value);
        $this->havingConditions[] = [$logic, $processed['clause']];
        array_push($this->havingBindings, ...$processed['bindings']);
    }

    private function validateOperator(string $operator): void
    {
        $allowed = array_merge(self::VALUE_OPERATORS, self::NULL_OPERATORS);
        if (!in_array($operator, $allowed)) {
            throw new QueryBuilderException("Invalid operator: $operator");
        }
    }

    private function processCondition(string $field, string $operator, mixed $value): array
    {
        $field = $this->applyAliasToField($field);

        if (in_array($operator, self::NULL_OPERATORS)) {
            return [
                'clause' => "$field $operator",
                'bindings' => []
            ];
        }

        if ($operator === 'IN') {
            return [
                'clause' => "$field IN ?a",
                'bindings' => [(array)$value]
            ];
        }

        if ($operator === 'BETWEEN') {
            $arr = is_array($value) ? array_values($value) : (array)$value;
            if (count($arr) !== 2) {
                throw new QueryBuilderException("BETWEEN operator expects 2 values.");
            }
            return [
                'clause' => "$field BETWEEN ?s AND ?s",
                'bindings' => $arr,
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

    private function applyAliasToField(string $field): string
    {
        if (!$this->alias || str_contains($field, '.')) {
            return $field;
        }
        return $this->alias . '.' . $field;
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
}