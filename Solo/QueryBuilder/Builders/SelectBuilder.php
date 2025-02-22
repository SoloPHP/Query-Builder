<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Builders;

use Solo\Database;
use Solo\QueryBuilder\Components\ConditionBuilder;
use Solo\QueryBuilder\Builders\CountBuilder;
use Solo\QueryBuilder\Exceptions\QueryBuilderException;
use Solo\QueryBuilder\Traits\TableParserTrait;
use Closure;
use stdClass;

final class SelectBuilder
{
    use TableParserTrait;

    private Database $db;
    private array $fields = ['*'];
    private string $table = '';
    private string $alias = '';
    private array $joins = [];
    private ConditionBuilder $conditionBuilder;
    private array $orderBy = [];
    private string $groupBy = '';
    private string $limit = '';
    private bool $distinct = false;

    public function __construct(Database $db, array $fields = ['*'])
    {
        $this->db = $db;
        $this->fields = $fields;
        $this->conditionBuilder = new ConditionBuilder('');
    }

    public function select(array $fields = ['*']): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function from(string $table): self
    {
        [$tableName, $alias] = $this->parseTable($table);
        $this->table = $tableName;
        $this->alias = $alias;
        $this->conditionBuilder = new ConditionBuilder($alias);
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        [$joinTable, $joinAlias] = $this->parseTable($table);
        $joinClause = "$type JOIN $joinTable AS $joinAlias ON $condition";
        $this->joins[] = $joinClause;
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
        $processedField = $this->applyAliasToField($field);
        $this->conditionBuilder->andWhere($processedField, $operator, $value);
        return $this;
    }

    public function orWhere(string $field, string $operator, mixed $value = null): self
    {
        $processedField = $this->applyAliasToField($field);
        $this->conditionBuilder->orWhere($processedField, $operator, $value);
        return $this;
    }

    public function whereBetween(string $field, mixed $start, mixed $end): self
    {
        $processedField = $this->applyAliasToField($field);
        $this->conditionBuilder->andBetween($processedField, $start, $end);
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->conditionBuilder->andRaw($sql, $bindings);
        return $this;
    }

    public function whereGroup(Closure $callback): self
    {
        $this->conditionBuilder->andGroup($callback);
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$field, $direction];
        return $this;
    }

    public function addOrderBy(string $field, string $direction = 'ASC'): self
    {
        return $this->orderBy($field, $direction);
    }

    public function groupBy(string $field): self
    {
        $field = $this->conditionBuilder->applyAlias($field, $this->alias);
        $this->groupBy = "GROUP BY $field";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = ($offset > 0)
            ? "LIMIT $limit OFFSET $offset"
            : "LIMIT $limit";

        return $this;
    }

    public function paginate(int $page, int $limit): self
    {
        $offset = ($page - 1) * $limit;
        return $this->limit($limit, $offset);
    }

    public function get(?int $fetchMode = null): array
    {
        if (empty($this->table)) {
            throw new QueryBuilderException('Table not specified. Use from() method.');
        }

        $sql = $this->toSql();
        return $this->db->query($sql)->fetchAll($fetchMode);
    }

    public function getOne(?int $fetchMode = null): array|stdClass|null
    {
        $this->limit(1);
        $rows = $this->get($fetchMode);
        return $rows[0] ?? null;
    }

    public function getIndexedBy(string $field): array
    {
        return array_column($this->get(), null, $field);
    }

    public function toCountBuilder(): CountBuilder
    {
        return new CountBuilder(
            $this->db,
            $this->table,
            $this->alias,
            $this->joins,
            $this->conditionBuilder,
            $this->groupBy
        );
    }

    public function count(): int
    {
        return $this->toCountBuilder()->getValue();
    }

    public function toSql(): string
    {
        $select  = $this->buildSelectClause();
        $from    = " FROM ?t AS ?c";
        $joins   = $this->joins ? ' ' . implode(' ', $this->joins) : '';
        $where   = $this->conditionBuilder->hasConditions()
            ? ' WHERE ' . $this->conditionBuilder->build()
            : '';
        $group   = $this->groupBy ? ' ' . $this->groupBy : '';
        $order   = $this->buildOrderByClause();
        $limit   = $this->limit ? " $this->limit" : '';

        $rawSql  = "$select$from$joins$where$group$order$limit";
        $params  = array_merge([$this->table, $this->alias], $this->conditionBuilder->getBindings());

        return $this->db->prepare($rawSql, ...$params);
    }

    private function buildSelectClause(): string
    {
        $processedFields = array_map([$this, 'processField'], $this->fields);
        $keyword = $this->distinct ? 'SELECT DISTINCT' : 'SELECT';
        return $keyword . ' ' . implode(', ', $processedFields);
    }

    private function buildOrderByClause(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        $parts = [];
        foreach ($this->orderBy as [$field, $direction]) {
            $parts[] = $this->applyAliasToField($field) . ' ' . strtoupper($direction);
        }
        return ' ORDER BY ' . implode(', ', $parts);
    }

    private function processField(string $field): string
    {
        if ($field === '*') {
            return $this->alias ? "$this->alias.*" : '*';
        }

        if (preg_match('/\b(COUNT|SUM|AVG|MIN|MAX)\s*\(/i', $field)) {
            return $field;
        }

        return $this->applyAliasToField($field);
    }

    private function applyAliasToField(string $field): string
    {
        if (str_contains($field, '.')) {
            return $field;
        }
        return $this->alias ? "$this->alias.$field" : $field;
    }
}