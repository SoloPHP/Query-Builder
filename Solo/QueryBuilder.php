<?php declare(strict_types=1);

namespace Solo;

use Solo\QueryBuilder\Builders\SelectBuilder;
use Solo\QueryBuilder\Builders\UpdateBuilder;
use Solo\QueryBuilder\Builders\InsertBuilder;
use Solo\QueryBuilder\Builders\DeleteBuilder;
use Solo\QueryBuilder\Traits\TableParserTrait;

readonly class QueryBuilder
{
    use TableParserTrait;

    public function __construct(
        private Database $db
    )
    {
    }

    public function select(array $fields = ['*'], array $bindings = []): SelectBuilder
    {
        return new SelectBuilder($this->db, $fields, $bindings);
    }

    public function insert(string $table): InsertBuilder
    {
        [$tableName] = $this->parseTable($table);
        return new InsertBuilder($this->db, $tableName);
    }

    public function update(string $table): UpdateBuilder
    {
        [$tableName] = $this->parseTable($table);
        return new UpdateBuilder($this->db, $tableName);
    }

    public function delete(string $table): DeleteBuilder
    {
        [$tableName] = $this->parseTable($table);
        return new DeleteBuilder($this->db, $tableName);
    }

    public function db(): Database
    {
        return $this->db;
    }
}