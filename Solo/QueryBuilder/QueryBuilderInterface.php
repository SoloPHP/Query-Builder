<?php declare(strict_types=1);

namespace Solo\QueryBuilder;

use Closure;
use InvalidArgumentException;

interface QueryBuilderInterface
{
    /**
     * Select fields to retrieve.
     *
     * @param array $fields Array of fields to select.
     * @return self
     */
    public function select(array $fields = ['*']): self;

    /**
     * Enable DISTINCT selection.
     *
     * @return self
     */
    public function distinct(): self;

    /**
     * Add a JOIN clause.
     *
     * @param string $table Table name to join.
     * @param string $onCondition ON condition for the join.
     * @param string $type Join type (e.g., INNER, LEFT).
     * @param string|null $alias Alias for the joined table.
     * @return self
     */
    public function join(string $table, string $onCondition, string $type = 'INNER', ?string $alias = null): self;

    /**
     * Add an INNER JOIN clause.
     *
     * @param string $table Table name to join.
     * @param string $onCondition ON condition for the join.
     * @param string|null $alias Alias for the joined table.
     * @return self
     */
    public function innerJoin(string $table, string $onCondition, ?string $alias = null): self;

    /**
     * Add a LEFT JOIN clause.
     *
     * @param string $table Table name to join.
     * @param string $onCondition ON condition for the join.
     * @param string|null $alias Alias for the joined table.
     * @return self
     */
    public function leftJoin(string $table, string $onCondition, ?string $alias = null): self;

    /**
     * Add a RIGHT JOIN clause.
     *
     * @param string $table Table name to join.
     * @param string $onCondition ON condition for the join.
     * @param string|null $alias Alias for the joined table.
     * @return self
     */
    public function rightJoin(string $table, string $onCondition, ?string $alias = null): self;

    /**
     * Add a GROUP BY clause.
     *
     * @param string $field Field to group by.
     * @return self
     */
    public function groupBy(string $field): self;

    /**
     * Add an ORDER BY clause.
     *
     * @param string $field Field to order by.
     * @param string $direction Sorting direction (ASC/DESC).
     * @return self
     */
    public function orderBy(string $field, string $direction = 'ASC'): self;

    /**
     * Append an additional ORDER BY clause.
     *
     * @param string $field Field to order by.
     * @param string $direction Sorting direction (ASC/DESC).
     * @return self
     */
    public function addOrderBy(string $field, string $direction = 'ASC'): self;

    /**
     * Add a LIMIT clause.
     *
     * @param int $limit Number of rows to return.
     * @param int $offset Number of rows to skip.
     * @return self
     */
    public function limit(int $limit, int $offset = 0): self;

    /**
     * Paginate results.
     *
     * @param int $page Page number (starts from 1).
     * @param int $limit Number of rows per page.
     * @return self
     */
    public function paginate(int $page = 1, int $limit = 10): self;

    /**
     * Add a raw SQL WHERE condition.
     *
     * @param string $sql Raw SQL condition.
     * @param array $bindings Bindings for the SQL.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     */
    public function whereRaw(string $sql, array $bindings = [], string $logic = 'AND'): self;

    /**
     * Add a basic WHERE condition.
     *
     * @param string $field Field name.
     * @param string $operator Comparison operator (e.g., '=', '>', 'LIKE').
     * @param mixed $value Value to compare.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     * @throws InvalidArgumentException If operator is invalid.
     */
    public function where(string $field, string $operator, mixed $value, string $logic = 'AND'): self;

    /**
     * Add a WHERE IN condition.
     *
     * @param string $field Field name.
     * @param array $values Array of values.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     * @throws InvalidArgumentException If values are empty.
     */
    public function whereIn(string $field, array $values, string $logic = 'AND'): self;

    /**
     * Add a WHERE IS NULL condition.
     *
     * @param string $field Field name.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     */
    public function whereNull(string $field, string $logic = 'AND'): self;

    /**
     * Add a WHERE IS NOT NULL condition.
     *
     * @param string $field Field name.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     */
    public function whereNotNull(string $field, string $logic = 'AND'): self;

    /**
     * Add a WHERE LIKE condition.
     *
     * @param string $field Field name.
     * @param string $pattern LIKE pattern.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     */
    public function whereLike(string $field, string $pattern, string $logic = 'AND'): self;

    /**
     * Add a WHERE BETWEEN condition.
     *
     * @param string $field Field name.
     * @param mixed $start Start value.
     * @param mixed $end End value.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     */
    public function whereBetween(string $field, mixed $start, mixed $end, string $logic = 'AND'): self;

    /**
     * Group WHERE conditions using a closure.
     *
     * @param Closure $callback Callback to define nested conditions.
     * @param string $logic Logical operator (AND/OR).
     * @return self
     */
    public function whereGroup(Closure $callback, string $logic = 'AND'): self;

    /**
     * Insert a new record.
     *
     * @param array $data Data to insert.
     * @return bool True on success.
     */
    public function insert(array $data): bool;

    /**
     * Update a record by primary key.
     *
     * @param array $data Data to update.
     * @param string $primaryKey Primary key field name.
     * @param int|string $id Record ID.
     * @return bool True on success.
     */
    public function update(array $data, string $primaryKey, int|string $id): bool;

    /**
     * Delete a record by primary key.
     *
     * @param string $primaryKey Primary key field name.
     * @param int|string $id Record ID.
     * @return bool True on success.
     */
    public function delete(string $primaryKey, int|string $id): bool;

    /**
     * Execute the query and fetch all results.
     *
     * @return array Result set.
     */
    public function get(): array;

    /**
     * Execute the query and fetch the first result.
     *
     * @return array|null First row or null if empty.
     */
    public function getOne(): ?array;

    /**
     * Execute the query and fetch all results indexed by the specified field.
     *
     * @param string $field Field to use as array index.
     * @return array Result set indexed by the specified field.
     */
    public function getIndexedBy(string $field): array;

    /**
     * Get the total count of records matching the query conditions.
     *
     * Does not modify the current builder state — all changes are temporary
     * and automatically reverted after execution.
     *
     * @return int The number of matching records.
     */
    public function count(): int;

    /**
     * Reset the query builder to its initial state.
     *
     * @return self
     */
    public function reset(): self;

    /**
     * Get the generated SQL query.
     *
     * @return string SQL string.
     */
    public function toSql(): string;
}