# Solo Query Builder 🛠

[![Version](https://img.shields.io/badge/version-1.4.0-blue.svg)](https://github.com/solophp/query-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

A lightweight, fluent SQL query builder for PHP, providing secure and intuitive database interactions.

## ✨ Features

- **Fluent Interface**: Chainable methods for building queries.
- **CRUD Support**: Quickly create, read, update, and delete.
- **Secure Binding**: Automatic placeholder handling to prevent SQL injection.
- **Alias Parsing**: Easy table aliasing (e.g., `answers_services|a`).
- **Condition Groups**: Complex `WHERE` clauses with closures.
- **Join Clauses**: `INNER JOIN`, `LEFT JOIN`, `RIGHT JOIN` support in `SELECT` and `UPDATE`.
- **Raw SQL**: Safely insert raw SQL snippets when needed.
- **RawExpression Support**: Use RawExpression objects in select fields for complex expressions.
- **Select Bindings**: Add parameters directly to the select statement for secure value binding.
- **HAVING Support**: Add `HAVING` clauses the same way you use `WHERE`.
- **Field Mapping**: Consistent field mapping for both search and orderBy operations across joins.
- **searchAll / searchAny**: Flexible LIKE-based filtering across multiple fields (match ALL or ANY).
- **Conditional WHERE**: Use `when()`, `andWhen()`, `orWhen()` to add conditions only if value is not null.
## 📥 Installation

Install via Composer:

```bash
composer require solophp/query-builder
```

## 🚀 Usage

### Initialization

```php
use App\Core\QueryBuilder\QueryBuilder;
use Solo\Database;

$db = new Database(/* your config */);
$qb = new QueryBuilder($db);
```

## 📘 API Reference

### Core Methods

| Method                       | Description                                          |
|-----------------------------|------------------------------------------------------|
| `select(array $fields, array $bindings = [])`     | Initiate a `SELECT` query with optional bindings.         |
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
| `when(string $field, string $operator, mixed $value)`   | WHERE condition only if value is not null.                                 |
| `andWhen(string $field, string $operator, mixed $value)`| AND condition only if value is not null.                                   |
| `orWhen(string $field, string $operator, mixed $value)` | OR condition only if value is not null.                                    |
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
| `orderBy(?string $field, ?string $direction, array $fieldMap = [])` | ORDER BY clause with optional field mapping support.            |
| `addOrderBy(string $field, string $direction, array $fieldMap = [])` | Add additional order criteria with mapping support.            |
| `limit(int $limit, int $offset = 0)`                    | Limit and offset for pagination.                                           |
| `paginate(int $page, int $limit)`                       | Paginate by page number.                                                   |
| `searchAll(?array $search, array $fields)`              | LIKE search across multiple fields (match all).                            |
| `searchAny(?array $search, array $fields)`              | LIKE search across multiple fields (match any).                            |
### Execution & Results

| Method                           | Description                                               |
|----------------------------------|-----------------------------------------------------------|
| `get(?int $fetchMode = null)`    | Execute SELECT and return all rows.                       |
| `getFirst(?int $fetchMode = null)` | Execute SELECT with `LIMIT 1` and return a single row.    |
| `getFieldValue(string $field)`   | Fetch a single field value from the first result row.     |
| `getFieldValues(string $field)`  | Fetch an array of all values for a specific field.        |
| `getIndexedBy(string $field)`    | Return an associative array indexed by a specific field.  |
| `count()`                        | Execute a `SELECT COUNT(*)` using the current conditions. |
| `execute()`                      | Execute `INSERT`, `UPDATE`, or `DELETE`.                  |
| `compile()`                      | Return the generated SQL string without executing.        |

## 📚 Examples

### SELECT

```php
$results = $qb
    ->select(['id', 'title'])
    ->from('posts|p')
    ->where('p.created_at', '>', '2023-01-01')
    ->leftJoin('users|u', 'u.id = p.author_id')
    ->orderBy('p.id', 'DESC')
    ->limit(10)
    ->get();
```

### SELECT With Bindings

```php
$results = $qb
    ->select([
        'id', 
        'title', 
        'DATE_FORMAT(created_at, ?s) AS formatted_date'
    ], ['%Y-%m-%d'])
    ->from('posts')
    ->get();
```

### Using RawExpressions

```php
use Solo\Database\Expressions\RawExpression;

$results = $qb
    ->select([
        'u.id',
        'u.email',
        new RawExpression('CONCAT(u.first_name, " ", u.last_name) AS full_name'),
        new RawExpression('DATEDIFF(NOW(), u.created_at) AS days_registered')
    ])
    ->from('users|u')
    ->where('u.status', '=', 'active')
    ->get();
```

### SELECT WITH JOIN

```php
$results = $qb
    ->select(['u.id', 'u.name', 'p.phone'])
    ->from('users|u')
    ->leftJoin('profiles|p', 'p.user_id = u.id')
    ->where('u.status', '=', 'active')
    ->get();
```

### INSERT

```php
$qb->insert('posts')
    ->values([
        'title' => 'Hello World',
        'content' => 'Welcome to my blog!',
        'author_id' => 1
    ])
    ->execute();
```

### UPDATE

```php
$qb->update('posts')
    ->set(['title' => 'Updated Title'])
    ->where('id', '=', 5)
    ->execute();
```

### UPDATE with JOIN
```php
$qb->update('users AS u')
    ->innerJoin('profiles p', 'p.user_id = u.id')
    ->set(['u.status' => 'active', 'p.updated_at' => 'NOW()'])
    ->where('u.id', '=', 42)
    ->execute();
```

### DELETE

```php
$qb->delete('posts')
    ->where('id', 'IN', [4, 5, 6])
    ->execute();
```

### Raw SQL

```php
$qb->select()
    ->from('users')
    ->whereRaw('LENGTH(username) > ?i', [10])
    ->get();
```
### WHERE Grouping
```php
$qb->select()
    ->from('orders|o')
    ->whereGroup(function ($builder) {
        $builder->where('o.status', '=', 'pending')
                ->orWhere('o.payment_status', '=', 'failed');
    })
    ->get();
```
### HAVING
```php
$results = $qb
    ->select(['p.category', 'COUNT(p.id) AS total'])
    ->from('products|p')
    ->groupBy('p.category')
    ->having('total', '>', 10)
    ->get();
```
### Get Field Value
```php
$email = $qb->select(['email'])
    ->from('users')
    ->where('id', '=', 42)
    ->getFieldValue('email');
```
### Get Field Values
```php
$emails = $qb->select(['email'])
    ->from('users')
    ->where('status', '=', 'active')
    ->getFieldValues('email');
```
### Indexed Results
```php
$users = $qb->select()
    ->from('users')
    ->getIndexedBy('id'); 
```
### Count
```php
$total = $qb->select()
    ->from('posts')
    ->where('status', '=', 'published')
    ->count();
```
### searchAll Example (AND logic)
```php
$results = $qb
    ->select(['p.*', 'c.name AS category_name'])
    ->from('products|p')
    ->join('categories|c', 'c.id = p.category_id')
    ->searchAll([
        'product_name' => 'laptop',
        'category_name' => 'electronics'
    ], [
        'product_name' => 'p.name',
        'category_name' => 'c.name'
    ])
    ->get();
```
### searchAny Example (OR logic)
```php
$results = $qb
    ->select()
    ->from('products|p')
    ->searchAny([
        'name' => 'gaming laptop',
        'description' => 'portable'
    ], [
        'name' => 'p.name',
        'description' => 'p.description'
    ])
    ->get();
```
## ✅ Requirements

- PHP 8.2+
- [solophp/database](https://github.com/solophp/database) for database connections

## 📄 License

MIT License. See [LICENSE](LICENSE).