# Solo Query Builder 🛠

[![Version](https://img.shields.io/badge/version-1.0.1-blue.svg)](https://github.com/solophp/query-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

A flexible and secure SQL query builder for PHP, designed to simplify database interactions with enhanced condition logic.

## ✨ Features

- **Fluent Interface**: Chainable methods for building complex queries.
- **Parameter Binding**: Automatic prevention of SQL injection.
- **CRUD Support**: Methods for `INSERT`, `UPDATE`, `DELETE`.
- **Advanced Conditions**: Support for `WHERE` (with `AND`/`OR` logic), `JOIN`, `GROUP BY`, `ORDER BY`.
- **Condition Groups**: Group conditions using parentheses.
- **Raw SQL**: Safely include raw SQL snippets.

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
$users = $qb->select(['name', 'email'])
    ->where('age', '>', 25)
    ->orderBy('name', 'DESC')
    ->limit(10)
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

| Method          | Description                                                                 |
|-----------------|-----------------------------------------------------------------------------|
| `get()`         | Fetch all results as an array                                              |
| `getOne()`      | Retrieve first result row (`null` if empty)                                |
| `count()`       | **Get total matching records** (ignores LIMIT/OFFSET)                      |
| `toSql()`       | Generate SQL query for debugging                                           |
| `reset()`       | Reset builder to initial state                                             |

### Query Construction

| Method               | Parameters                              | Example Usage                          |
|----------------------|-----------------------------------------|----------------------------------------|
| `select()`           | `array $fields`                         | `->select(['id', 'name'])`             |
| `distinct()`         | -                                       | `->distinct()->get()`                  |
| `join()`             | `string $table, string $onCondition`    | `->join('posts', 'users.id = posts.user_id')` |
| `groupBy()`          | `string $field`                         | `->groupBy('department')`              |
| `orderBy()`          | `string $field, string $direction`      | `->orderBy('created_at', 'DESC')`      |

### Conditions (WHERE)

| Method                | Example Usage                             |
|-----------------------|-------------------------------------------|
| `where()`             | `->where('age', '>', 25)`                 |
| `whereIn()`           | `->whereIn('role', ['admin', 'user'])`    |
| `whereNull()`         | `->whereNull('deleted_at')`               |
| `whereBetween()`      | `->whereBetween('price', 100, 500)`       |
| `whereRaw()`          | `->whereRaw('DATE(created) = ?s', ['2023-01-01'])` |

### CRUD Operations

```php
// Insert
$qb->insert(['name' => 'John', 'email' => 'john@example.com']);

// Update
$qb->update(['email' => 'new@example.com'], 'id', 42);

// Delete
$qb->delete('id', 42);
```
## ✅ Requirements

- PHP 8.2+
- [solophp/database](https://github.com/solophp/database) for database connections

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.
```