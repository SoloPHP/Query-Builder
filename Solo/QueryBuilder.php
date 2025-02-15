<?php declare(strict_types=1);

namespace Solo;

use Solo\QueryBuilder\QueryBuilderInterface;
use Solo\QueryBuilder\ConditionBuilder;
use Closure;

class QueryBuilder implements QueryBuilderInterface
{
    protected array $fields = ['*'];
    protected string $orderBy = '';
    protected string $groupBy = '';
    protected string $limit = '';
    protected array $joins = [];
    protected bool $distinct = false;
    protected ConditionBuilder $conditionBuilder;

    public function __construct(
        protected Database $db,
        protected string $table,
        protected ?string $alias = null
    )
    {
        $this->alias = $alias ?? substr($table, 0, 1);
        $this->conditionBuilder = new ConditionBuilder($this->alias);
    }

    public function select(array $fields = ['*']): self
    {
        $this->fields = array_map(
            function ($field) {
                if (preg_match('/\b(COUNT|SUM|AVG|MIN|MAX)\s*\(/i', $field)) {
                    return $field;
                }
                return $this->conditionBuilder->applyAlias($field);
            },
            $fields
        );
        return $this;
    }

    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    public function join(string $table, string $onCondition, string $type = 'INNER', ?string $alias = null): self
    {
        $alias = $alias ?? substr($table, 0, 1);
        $this->joins[] = "$type JOIN $table AS $alias ON $onCondition";
        return $this;
    }

    public function innerJoin(string $table, string $onCondition, ?string $alias = null): self
    {
        return $this->join($table, $onCondition, 'INNER', $alias);
    }

    public function leftJoin(string $table, string $onCondition, ?string $alias = null): self
    {
        return $this->join($table, $onCondition, 'LEFT', $alias);
    }

    public function rightJoin(string $table, string $onCondition, ?string $alias = null): self
    {
        return $this->join($table, $onCondition, 'RIGHT', $alias);
    }

    public function groupBy(string $field): self
    {
        $this->groupBy = "GROUP BY " . $this->conditionBuilder->applyAlias($field);
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy = "ORDER BY " . $this->conditionBuilder->applyAlias($field) . " $direction";
        return $this;
    }

    public function addOrderBy(string $field, string $direction = 'ASC'): self
    {
        $field = $this->conditionBuilder->applyAlias($field);
        if ($this->orderBy) {
            $this->orderBy .= ", $field $direction";
        } else {
            $this->orderBy = "ORDER BY $field $direction";
        }
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = "LIMIT $offset, $limit";
        return $this;
    }

    public function paginate(int $page = 1, int $limit = 10): self
    {
        $offset = ($page - 1) * $limit;
        return $this->limit($limit, $offset);
    }

    public function whereRaw(string $sql, array $bindings = [], string $logic = 'AND'): self
    {
        $this->conditionBuilder->whereRaw($sql, $bindings, $logic);
        return $this;
    }

    public function where(string $field, string $operator, mixed $value, string $logic = 'AND'): self
    {
        $this->conditionBuilder->where($field, $operator, $value, $logic);
        return $this;
    }

    public function whereIn(string $field, array $values, string $logic = 'AND'): self
    {
        $this->conditionBuilder->whereIn($field, $values, $logic);
        return $this;
    }

    public function whereNull(string $field, string $logic = 'AND'): self
    {
        $this->conditionBuilder->whereNull($field, $logic);
        return $this;
    }

    public function whereNotNull(string $field, string $logic = 'AND'): self
    {
        $this->conditionBuilder->whereNotNull($field, $logic);
        return $this;
    }

    public function whereLike(string $field, string $pattern, string $logic = 'AND'): self
    {
        $this->conditionBuilder->whereLike($field, $pattern, $logic);
        return $this;
    }

    public function whereBetween(string $field, mixed $start, mixed $end, string $logic = 'AND'): self
    {
        $this->conditionBuilder->whereBetween($field, $start, $end, $logic);
        return $this;
    }

    public function whereGroup(Closure $callback, string $logic = 'AND'): self
    {
        $this->conditionBuilder->whereGroup($callback, $logic);
        return $this;
    }

    public function insert(array $data): bool
    {
        return $this->db->query(
                "INSERT INTO ?t SET ?A",
                $this->table,
                $data
            )->rowCount() > 0;
    }

    public function update(array $data, string $primaryKey, int|string $id): bool
    {
        $placeholder = is_int($id) ? '?i' : '?s';
        $query = "UPDATE ?t SET ?A WHERE ?c = {$placeholder}";

        return $this->db->query(
                $query,
                $this->table,
                $data,
                $primaryKey,
                $id
            )->rowCount() > 0;
    }

    public function delete(string $primaryKey, int|string $id): bool
    {
        $placeholder = is_int($id) ? '?i' : '?s';
        $query = "DELETE FROM ?t WHERE ?c = {$placeholder}";

        return $this->db->query(
                $query,
                $this->table,
                $primaryKey,
                $id
            )->rowCount() > 0;
    }

    public function get(): array
    {
        return $this->db->query(
            $this->buildQuery(),
            ...$this->conditionBuilder->getBindings()
        )->fetchAll();
    }

    public function getOne(): ?array
    {
        $this->limit(1);
        return $this->get()[0] ?? null;
    }

    public function getIndexedBy(string $field): array
    {
        return array_column($this->get(), null, $field);
    }

    public function count(): int
    {
        $originalSelect = $this->fields;
        $originalGroupBy = $this->groupBy;
        $originalOrderBy = $this->orderBy;
        $originalLimit = $this->limit;

        $this->select(['COUNT(*) as count']);
        $this->groupBy = '';
        $this->orderBy = '';
        $this->limit = '';

        $result = $this->getOne();
        $count = (int) ($result['count'] ?? 0);

        $this->fields = $originalSelect;
        $this->groupBy = $originalGroupBy;
        $this->orderBy = $originalOrderBy;
        $this->limit = $originalLimit;

        return $count;
    }

    public function reset(): self
    {
        $this->conditionBuilder->reset();
        $this->fields = ['*'];
        $this->orderBy = '';
        $this->groupBy = '';
        $this->limit = '';
        $this->joins = [];
        $this->distinct = false;
        return $this;
    }

    public function toSql(): string
    {
        return $this->buildQuery();
    }

    private function buildQuery(): string
    {
        $sql = "SELECT " . ($this->distinct ? "DISTINCT " : "") . implode(", ", $this->fields);
        $sql .= " FROM {$this->table} AS {$this->alias}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(" ", $this->joins);
        }

        $whereClause = $this->conditionBuilder->buildConditions();
        if (!empty($whereClause)) {
            $sql .= " WHERE " . $whereClause;
        }

        if (!empty($this->groupBy)) {
            $sql .= " " . $this->groupBy;
        }

        if (!empty($this->orderBy)) {
            $sql .= " " . $this->orderBy;
        }

        if (!empty($this->limit)) {
            $sql .= " " . $this->limit;
        }

        return $sql;
    }
}