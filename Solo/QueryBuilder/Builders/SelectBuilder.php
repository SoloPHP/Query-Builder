<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Builders;

use Closure;
use stdClass;
use Solo\Database;
use Solo\QueryBuilder\Components\ConditionBuilder;
use Solo\QueryBuilder\Builders\CountBuilder;
use Solo\QueryBuilder\Exceptions\QueryBuilderException;
use Solo\QueryBuilder\Traits\TableParserTrait;

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

    /*------------------------------------------------------------------------*
     *                     WHERE
     *------------------------------------------------------------------------*/
    public function where(string $field, string $operator, mixed $value = null): self
    {
        $this->conditionBuilder->where($field, $operator, $value);
        return $this;
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

    public function whereBetween(string $field, mixed $start, mixed $end): self
    {
        $this->conditionBuilder->andBetween($field, $start, $end);
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

    /*------------------------------------------------------------------------*
     *                     HAVING
     *------------------------------------------------------------------------*/
    public function having(string $field, string $operator, mixed $value = null): self
    {
        $this->conditionBuilder->having($field, $operator, $value);
        return $this;
    }

    public function andHaving(string $field, string $operator, mixed $value = null): self
    {
        $this->conditionBuilder->andHaving($field, $operator, $value);
        return $this;
    }

    public function orHaving(string $field, string $operator, mixed $value = null): self
    {
        $this->conditionBuilder->orHaving($field, $operator, $value);
        return $this;
    }

    public function havingBetween(string $field, mixed $start, mixed $end): self
    {
        $this->conditionBuilder->andHavingBetween($field, $start, $end);
        return $this;
    }

    public function havingRaw(string $sql, array $bindings = []): self
    {
        $this->conditionBuilder->andHavingRaw($sql, $bindings);
        return $this;
    }

    public function havingGroup(Closure $callback): self
    {
        $this->conditionBuilder->andHavingGroup($callback);
        return $this;
    }

    /*------------------------------------------------------------------------*
    *                     GROUP BY, ORDER BY, LIMIT
    *------------------------------------------------------------------------*/
    public function groupBy(string $field): self
    {
        if ($this->alias && !str_contains($field, '.')) {
            $field = "{$this->alias}.{$field}";
        }
        $this->groupBy = "GROUP BY $field";
        return $this;
    }

    public function orderBy(?string $field, string $direction = 'ASC'): self
    {
        if (!$field) {
            return $this;
        }

        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }

        $this->orderBy[] = [$field, $direction];
        return $this;
    }

    public function addOrderBy(string $field, string $direction = 'ASC'): self
    {
        return $this->orderBy($field, $direction);
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

    /*------------------------------------------------------------------------*
     *                     SEARCH
     *------------------------------------------------------------------------*/

    public function search(?string $search, array $searchableFields = []): self
    {
        if (!$search || empty($searchableFields)) {
            return $this;
        }

        $field = $searchableFields[0];
        $value = $search;

        if (str_contains($search, ':')) {
            [$f, $v] = explode(':', $search, 2);

            if (!in_array($f, $searchableFields, true)) {
                return $this;
            }

            $field = $f;
            $value = $v;
        }

        foreach (explode(' ', $value) as $kw) {
            $kw = trim($kw);
            if ($kw === '') {
                continue;
            }
            $this->conditionBuilder->andWhere($field, 'LIKE', $kw);
        }

        return $this;
    }

    /*------------------------------------------------------------------------*
     *                     EXECUTING QUERIES
     *------------------------------------------------------------------------*/
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

    /*------------------------------------------------------------------------*
     *                     SQL ASSEMBLY
     *------------------------------------------------------------------------*/
    public function toSql(): string
    {
        $select = $this->buildSelectClause();
        $from = " FROM ?t AS ?c";
        $joins = $this->joins ? ' ' . implode(' ', $this->joins) : '';

        $where = '';
        if ($this->conditionBuilder->hasWhereConditions()) {
            $whereSql = $this->conditionBuilder->buildWhere();
            $where = " WHERE $whereSql";
        }

        $group = $this->groupBy ? ' ' . $this->groupBy : '';

        $having = '';
        if ($this->conditionBuilder->hasHavingConditions()) {
            $havingSql = $this->conditionBuilder->buildHaving();
            $having = " HAVING $havingSql";
        }

        $order = $this->buildOrderByClause();

        $limit = $this->limit ? " $this->limit" : '';

        $rawSql = "$select$from$joins$where$group$having$order$limit";

        $params = array_merge(
            [$this->table, $this->alias],
            $this->conditionBuilder->getWhereBindings(),
            $this->conditionBuilder->getHavingBindings()
        );

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
            if ($this->alias && !str_contains($field, '.')) {
                $field = "{$this->alias}.{$field}";
            }
            $parts[] = $field . ' ' . strtoupper($direction);
        }
        return ' ORDER BY ' . implode(', ', $parts);
    }

    private function processField(string $field): string
    {
        if ($field === '*') {
            return $this->alias ? "{$this->alias}.*" : '*';
        }

        if (preg_match('/\b(COUNT|SUM|AVG|MIN|MAX)\s*\(/i', $field)) {
            return $field;
        }

        if ($this->alias && !str_contains($field, '.')) {
            return "{$this->alias}.{$field}";
        }
        return $field;
    }
}