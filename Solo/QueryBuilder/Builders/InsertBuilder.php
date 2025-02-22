<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Builders;

use Solo\Database;

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

    public function toSql(): string
    {
        return $this->db->prepare(
            "INSERT INTO ?t SET ?A",
            $this->table,
            $this->data
        );
    }

    public function execute(): string|false
    {
        return $this->db->query($this->toSql())->lastInsertId();
    }
}