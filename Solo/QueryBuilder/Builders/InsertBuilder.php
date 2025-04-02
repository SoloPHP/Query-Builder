<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Builders;

use Solo\Database;
use Solo\QueryBuilder\Exceptions\QueryBuilderException;
use Solo\Database\Expressions\RawExpression;

final class InsertBuilder
{
    private Database $db;
    private string $table;
    private array $data = [];

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function values(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function compile(): string
    {
        if (empty($this->data)) {
            throw new QueryBuilderException('No data specified for INSERT. Use values().');
        }

        $rawSql = "INSERT INTO ?t SET ?A";

        $params = [$this->table, $this->data];

        return $this->db->prepare($rawSql, ...$params);
    }

    public function execute(): string|false
    {
        return $this->db->query($this->compile())->lastInsertId();
    }
}