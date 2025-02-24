<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Builders;

use Solo\Database;
use Solo\QueryBuilder\Components\ConditionBuilder;

final class UpdateBuilder
{
    private Database $db;
    private string $table;
    private array $data = [];
    private ConditionBuilder $conditionBuilder;

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
        $this->conditionBuilder = new ConditionBuilder();
    }

    public function set(array $data): self
    {
        $this->data = $data;
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

    public function toSql(): string
    {
        $where = $this->conditionBuilder->hasWhereConditions()
            ? ' WHERE ' . $this->conditionBuilder->buildWhere()
            : '';

        return $this->db->prepare(
            "UPDATE ?t SET ?A{$where}",
            $this->table,
            $this->data,
            ...$this->conditionBuilder->getWhereBindings()
        );
    }

    public function execute(): int
    {
        return $this->db->query($this->toSql())->rowCount();
    }
}