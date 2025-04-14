<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Builders;

use Solo\Database;
use Solo\QueryBuilder\Components\ConditionBuilder;

final class DeleteBuilder
{
    private Database $db;
    private string $table;
    private ConditionBuilder $conditionBuilder;

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
        $this->conditionBuilder = new ConditionBuilder();
    }

    public function alias(string $alias): self
    {
        $this->conditionBuilder = new ConditionBuilder($alias);
        return $this;
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
        $where = $this->conditionBuilder->hasWhereConditions()
            ? ' WHERE ' . $this->conditionBuilder->buildWhere()
            : '';

        return $this->db->prepare(
            "DELETE FROM ?t{$where}",
            $this->table,
            ...$this->conditionBuilder->getWhereBindings()
        );
    }

    public function execute(): int
    {
        return $this->db->query($this->compile())->rowCount();
    }
}