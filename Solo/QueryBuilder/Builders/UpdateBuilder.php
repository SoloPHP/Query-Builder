<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Builders;

use Solo\Database;
use Solo\QueryBuilder\Components\ConditionBuilder;
use Solo\QueryBuilder\Exceptions\QueryBuilderException;
use Solo\QueryBuilder\Traits\TableParserTrait;
use Solo\Database\Expressions\RawExpression;

final class UpdateBuilder
{
    use TableParserTrait;

    private Database $db;
    private string $table = '';
    private string $alias = '';
    private array $joins = [];
    private array $data = [];
    private ConditionBuilder $conditionBuilder;

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;

        [$tableName, $alias] = $this->parseTable($table);
        $this->table = $tableName;
        $this->alias = $alias;

        $this->conditionBuilder = new ConditionBuilder($this->alias);
    }

    public function set(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        [$joinTable, $joinAlias] = $this->parseTable($table);

        $this->joins[] = "$type JOIN $joinTable AS $joinAlias ON $condition";
        return $this;
    }

    public function innerJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'INNER');
    }

    public function leftJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'LEFT');
    }

    public function rightJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'RIGHT');
    }

    public function where(string $field, string $operator, mixed $value = null): self
    {
        return $this->andWhere($field, $operator, $value);
    }

    public function andWhere(string $field, string $operator, mixed $value = null): self
    {
        $this->conditionBuilder->andWhere($field, $operator, $value);
        return $this;
    }

    public function orWhere(string $field, string $operator, mixed $value = null): self
    {
        $this->conditionBuilder->orWhere($field, $operator, $value);
        return $this;
    }

    public function when(string $field, string $operator, mixed $value = null): self
    {
        return $value !== null ? $this->where($field, $operator, $value) : $this;
    }

    public function andWhen(string $field, string $operator, mixed $value = null): self
    {
        return $value !== null ? $this->andWhere($field, $operator, $value) : $this;
    }

    public function orWhen(string $field, string $operator, mixed $value = null): self
    {
        return $value !== null ? $this->orWhere($field, $operator, $value) : $this;
    }

    public function compile(): string
    {
        if (empty($this->table)) {
            throw new QueryBuilderException('Table not specified in UpdateBuilder.');
        }

        $tablePart = $this->alias
            ? "{$this->table} AS {$this->alias}"
            : $this->table;

        $joinsSql = '';
        if (!empty($this->joins)) {
            $joinsSql = ' ' . implode(' ', $this->joins);
        }

        $whereSql = '';
        if ($this->conditionBuilder->hasWhereConditions()) {
            $where = $this->conditionBuilder->buildWhere();
            $whereSql = " WHERE $where";
        }

        $rawSql = "UPDATE $tablePart$joinsSql SET ?A{$whereSql}";

        $params = array_merge(
            [$this->data],
            $this->conditionBuilder->getWhereBindings()
        );

        return $this->db->prepare($rawSql, ...$params);
    }

    public function execute(): int
    {
        return $this->db->query($this->compile())->rowCount();
    }
}