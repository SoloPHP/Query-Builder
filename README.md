# Solo Query Builder 🛠

[![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)](https://github.com/solophp/query-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

A lightweight, fluent SQL query builder for PHP, providing secure and intuitive database interactions.

## ✨ Features

- **Fluent Interface**: Chainable methods for building queries.
- **CRUD Support**: Quickly create, read, update, and delete.
- **Secure Binding**: Automatic placeholder handling to prevent SQL injection.
- **Alias Parsing**: Easy table aliasing (e.g., `answers_services|a`).
- **Condition Groups**: Complex `WHERE` clauses with closures.
- **Join Clauses**: `INNER JOIN`, `LEFT JOIN`, `RIGHT JOIN` support.
- **Raw SQL**: Safely insert raw SQL snippets when needed.
- **HAVING Support**: Add `HAVING` clauses the same way you use `WHERE`.

## 📥 Installation

Install via Composer:

~~~bash
composer require solophp/query-builder
~~~

## 🚀 Usage

### Initialization

~~~php
use App\Core\QueryBuilder\QueryBuilder;
use Solo\Database;

$db = new Database(/* your config */);
$qb = new QueryBuilder($db);
~~~

### SELECT

~~~php
$results = $qb
    ->select(['id', 'title'])
    ->from('posts|p')
    ->where('p.created_at', '>', '2023-01-01')
    ->leftJoin('users|u', 'u.id = p.author_id')
    ->orderBy('p.id', 'DESC')
    ->limit(10)
    ->get();
~~~

### INSERT

~~~php
$qb->insert('posts')
    ->values([
        'title' => 'Hello World',
        'content' => 'Welcome to my blog!',
        'author_id' => 1
    ])
    ->execute();
~~~

### UPDATE

~~~php
$qb->update('posts')
    ->set(['title' => 'Updated Title'])
    ->where('id', '=', 5)
    ->execute();
~~~

### DELETE

~~~php
$qb->delete('posts')
    ->where('id', 'IN', [4, 5, 6])
    ->execute();
~~~

### Raw SQL

~~~php
$qb->select()
    ->from('users')
    ->whereRaw('LENGTH(username) > ?i', [10])
    ->get();
~~~

### Using WHERE

~~~php
$qb->select()
    ->from('orders|o')
    ->whereGroup(function ($builder) {
        $builder->where('o.status', '=', 'pending')
                ->orWhere('o.payment_status', '=', 'failed');
    })
    ->get();
~~~

### Using HAVING

You can add `HAVING` conditions similarly to `WHERE`. For example, if you want to find categories with more than 10 products:

~~~php
$results = $qb
    ->select(['p.category', 'COUNT(p.id) AS total'])
    ->from('products|p')
    ->groupBy('p.category')
    ->having('total', '>', 10)
    ->get();
~~~

You can also chain multiple conditions, use `andHavingGroup()`, `orHaving()`, etc., just like with `WHERE`.

## 📘 API Reference

### Core Methods

| Method                       | Description                                          |
|-----------------------------|------------------------------------------------------|
| `select(array $fields)`     | Initiate a `SELECT` query.                          |
| `insert(string $table)`     | Initiate an `INSERT` query.                         |
| `update(string $table)`     | Initiate an `UPDATE` query.                         |
| `delete(string $table)`     | Initiate a `DELETE` query.                          |

### Query Methods (SELECT)

| Method                                                  | Description                                                                |
|---------------------------------------------------------|----------------------------------------------------------------------------|
| `from(string $table)`                                   | Specify the table and optional alias.                                      |
| `distinct(bool $distinct = true)`                       | Use `SELECT DISTINCT`.                                                     |
| `join(string $table, string $condition, string $type)`  | Add a JOIN clause (INNER / LEFT / RIGHT).                                  |
| `leftJoin(string $table, string $condition)`            | Add a LEFT JOIN clause.                                                    |
| `rightJoin(string $table, string $condition)`           | Add a RIGHT JOIN clause.                                                   |
| `innerJoin(string $table, string $condition)`           | Add an INNER JOIN clause.                                                  |
| `where(string $field, string $operator, mixed $value)`  | Basic WHERE condition.                                                     |
| `andWhere(string $field, string $operator, mixed $value)` | AND condition (chained).                                                  |
| `orWhere(string $field, string $operator, mixed $value)`  | OR condition.                                                              |
| `whereBetween(string $field, mixed $start, mixed $end)` | WHERE BETWEEN condition.                                                   |
| `whereRaw(string $sql, array $bindings = [])`           | Insert a raw SQL snippet in WHERE.                                         |
| `whereGroup(Closure $callback)`                         | Group multiple conditions via a closure.                                   |
| `groupBy(string $field)`                                | GROUP BY a specified field.                                                |
| `having(string $field, string $operator, mixed $value)` | HAVING condition (works like `where`).                                     |
| `andHaving(string $field, string $operator, mixed $value)` | AND condition for HAVING.                                                |
| `orHaving(string $field, string $operator, mixed $value)`  | OR condition for HAVING.                                                 |
| `havingBetween(string $field, mixed $start, mixed $end)`  | HAVING BETWEEN condition.                                                |
| `havingRaw(string $sql, array $bindings = [])`          | Insert a raw SQL snippet in HAVING.                                        |
| `havingGroup(Closure $callback)`                        | Group multiple HAVING conditions via a closure.                            |
| `orderBy(string $field, string $direction)`             | ORDER BY clause.                                                           |
| `addOrderBy(string $field, string $direction)`          | Add additional order criteria.                                             |
| `limit(int $limit, int $offset = 0)`                    | Limit and offset for pagination.                                           |
| `paginate(int $page, int $limit)`                       | Paginate by page number.                                                  |

### Execution & Results

| Method                                       | Description                                                |
|----------------------------------------------|------------------------------------------------------------|
| `get(?int $fetchMode = null)`                | Execute SELECT and return all rows.                        |
| `getOne(?int $fetchMode = null)`             | Execute SELECT with `LIMIT 1` and return a single row.     |
| `getIndexedBy(string $field)`                | Return an associative array indexed by a specific field.   |
| `count()`                                    | Execute a `SELECT COUNT(*)` using the current conditions.  |
| `execute()`                                  | Execute `INSERT`, `UPDATE`, or `DELETE`.                   |
| `toSql()`                                    | Return the generated SQL string without executing.         |

### Examples

**Indexed Results:**

~~~php
$users = $qb->select()
    ->from('users')
    ->getIndexedBy('id'); 
// Returns array keyed by user ID
~~~

**Counting Records:**

~~~php
$total = $qb->select()
    ->from('posts')
    ->where('status', '=', 'published')
    ->count();
~~~

**Complex Grouping with WHERE:**

~~~php
$results = $qb->select()
    ->from('orders')
    ->whereGroup(function ($builder) {
        $builder->where('status', '=', 'pending')
                ->orWhere('priority', '>', 5);
    })
    ->get();
~~~

**Using HAVING:**

~~~php
$results = $qb->select(['p.category', 'COUNT(p.id) AS total'])
    ->from('products|p')
    ->groupBy('p.category')
    ->having('total', '>', 10)
    ->get();
~~~

## ✅ Requirements

- PHP 8.2+
- [solophp/database](https://github.com/solophp/database) for database connections

## 📄 License

MIT License. See [LICENSE](LICENSE).