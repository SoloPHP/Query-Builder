# Solo Query Builder 🛠

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/solophp/query-builder)
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

### Complex Conditions

~~~php
$qb->select()
    ->from('orders|o')
    ->whereGroup(function ($builder) {
        $builder->where('o.status', '=', 'pending')
                ->orWhere('o.payment_status', '=', 'failed');
    })
    ->get();
~~~

## 📘 API Reference

### Core Methods

| Method                       | Description                                          |
|-----------------------------|------------------------------------------------------|
| `select(array $fields)`     | Initiate a `SELECT` query.                          |
| `insert(string $table)`     | Initiate an `INSERT` query.                         |
| `update(string $table)`     | Initiate an `UPDATE` query.                         |
| `delete(string $table)`     | Initiate a `DELETE` query.                          |

### Query Methods (SELECT)

| Method                                          | Description                                         |
|-------------------------------------------------|-----------------------------------------------------|
| `from(string $table)`                           | Specify the table and optional alias.               |
| `distinct(bool $distinct = true)`               | Use `SELECT DISTINCT`.                              |
| `join(string $table, string $condition, string $type)` | Add a JOIN clause (INNER/LEFT/RIGHT).        |
| `leftJoin(string $table, string $condition)`    | Add a LEFT JOIN clause.                             |
| `rightJoin(string $table, string $condition)`   | Add a RIGHT JOIN clause.                            |
| `innerJoin(string $table, string $condition)`   | Add an INNER JOIN clause.                           |
| `where(string $field, string $operator, mixed $value)` | Basic WHERE condition.                     |
| `andWhere(string $field, string $operator, mixed $value)` | AND condition (chained).                    |
| `orWhere(string $field, string $operator, mixed $value)`  | OR condition.                                |
| `whereBetween(string $field, mixed $start, mixed $end)`   | WHERE BETWEEN condition.                     |
| `whereGroup(Closure $callback)`                 | Group multiple conditions via a closure.            |
| `groupBy(string $field)`                        | GROUP BY a specified field.                         |
| `orderBy(string $field, string $direction)`     | ORDER BY clause.                                    |
| `addOrderBy(string $field, string $direction)`  | Add additional order criteria.                      |

### Execution & Results

| Method                                       | Description                                                |
|----------------------------------------------|------------------------------------------------------------|
| `get()`                                      | Execute SELECT and return all rows.                        |
| `getOne()`                                   | Execute SELECT with `LIMIT 1` and return a single row.     |
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

**Complex Grouping:**

~~~php
$results = $qb->select()
    ->from('orders')
    ->whereGroup(function ($builder) {
        $builder->where('status', '=', 'pending')
                ->orWhere('priority', '>', 5);
    })
    ->get();
~~~

## ✅ Requirements

- PHP 8.2+
- [solophp/database](https://github.com/solophp/database) for database connections

## 📄 License

MIT License. See [LICENSE](LICENSE).