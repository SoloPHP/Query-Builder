# Solo Query Builder 🛠

[![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)](https://github.com/solophp/query-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

A flexible and secure SQL query builder for PHP, designed to simplify database interactions with enhanced condition logic.

## ✨ Features

- **Fluent Interface**: Chainable methods for building complex queries
- **Parameter Binding**: Automatic prevention of SQL injection
- **CRUD Support**: Methods for `INSERT`, `UPDATE`, `DELETE`
- **Advanced Conditions**: Support for `WHERE` (with `AND`/`OR` logic), `JOIN`, `GROUP BY`, `ORDER BY`
- **Condition Groups**: Group conditions using parentheses
- **Raw SQL**: Safely include raw SQL snippets
- **Pagination**: Built-in support for result pagination
- **Indexing**: Ability to index results by specific fields

## 📥 Installation

```bash
composer require solophp/query-builder
```

## 🚀 Quick Start

### Initialization

```php
use Solo\QueryBuilder;

$qb = new QueryBuilder($dbConnection, 'users', 'u');
```

### Counting Records

```php
$totalActiveUsers = $qb->where('status', '=', 'active')
    ->leftJoin('posts', 'users.id = posts.user_id')
    ->count(); // Returns integer
```

### Fetching Data

```php
// Basic fetching
$users = $qb->select(['name', 'email'])
    ->where('age', '>', 25)
    ->orderBy('name', 'DESC')
    ->limit(10)
    ->get();

// Get indexed results
$usersByEmail = $qb->select(['id', 'name', 'email'])
    ->getIndexedBy('email');

// Pagination
$usersPage = $qb->select(['*'])
    ->paginate(2, 20) // Page 2, 20 items per page
    ->get();
```

### Complex Conditions

```php
$qb->whereGroup(function ($q) {
    $q->whereNull('deleted_at')
      ->where('last_login', '>', '2023-01-01', 'OR');
});
```

## 📘 Complete Method Documentation

### Query Execution

| Method          | Description                                                                 | Return Type |
|-----------------|-----------------------------------------------------------------------------|-------------|
| `get()`         | Fetch all results as an array                                              | `array`     |
| `getOne()`      | Retrieve first result row                                                  | `array\|null` |
| `getIndexedBy()`| Get results indexed by specified field                                     | `array`     |
| `count()`       | Get total matching records (ignores LIMIT/OFFSET)                          | `int`       |
| `toSql()`       | Generate SQL query for debugging                                           | `string`    |
| `reset()`       | Reset builder to initial state                                             | `self`      |

### Query Construction

| Method           | Parameters                                         | Description                           |
|------------------|---------------------------------------------------|---------------------------------------|
| `select()`       | `array $fields = ['*']`                           | Select specific fields                |
| `distinct()`     | -                                                 | Enable DISTINCT selection             |
| `join()`         | `string $table, string $onCondition, string $type`| Add any type of JOIN                 |
| `innerJoin()`    | `string $table, string $onCondition`             | Add INNER JOIN                       |
| `leftJoin()`     | `string $table, string $onCondition`             | Add LEFT JOIN                        |
| `rightJoin()`    | `string $table, string $onCondition`             | Add RIGHT JOIN                       |
| `groupBy()`      | `string $field`                                   | Add GROUP BY clause                  |
| `orderBy()`      | `string $field, string $direction = 'ASC'`       | Set ORDER BY clause                  |
| `addOrderBy()`   | `string $field, string $direction = 'ASC'`       | Add additional ORDER BY              |
| `limit()`        | `int $limit, int $offset = 0`                    | Set LIMIT and OFFSET                 |
| `paginate()`     | `int $page = 1, int $limit = 10`                 | Enable pagination                    |

### Conditions (WHERE)

| Method           | Parameters                                      | Description                           |
|------------------|------------------------------------------------|---------------------------------------|
| `where()`        | `string $field, string $operator, mixed $value`| Add basic WHERE condition            |
| `whereIn()`      | `string $field, array $values`                 | Add WHERE IN condition               |
| `whereNull()`    | `string $field`                                | Add WHERE IS NULL condition          |
| `whereNotNull()` | `string $field`                                | Add WHERE IS NOT NULL condition      |
| `whereLike()`    | `string $field, string $pattern`               | Add WHERE LIKE condition             |
| `whereBetween()` | `string $field, mixed $start, mixed $end`      | Add WHERE BETWEEN condition          |
| `whereGroup()`   | `Closure $callback`                            | Group WHERE conditions               |
| `whereRaw()`     | `string $sql, array $bindings = []`            | Add raw WHERE condition              |

### CRUD Operations

```php
// Insert
$success = $qb->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);

// Update
$success = $qb->update(
    ['email' => 'new@example.com'],
    'id',
    42
);

// Delete
$success = $qb->delete('id', 42);
```

## ✅ Requirements

- PHP 8.2+
- [solophp/database](https://github.com/solophp/database) for database connections

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.