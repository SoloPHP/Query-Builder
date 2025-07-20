# Solo PHP Query Builder

A lightweight and flexible SQL query builder for PHP 8.2+ with support for multiple SQL dialects and connection pooling.

[![Version](https://img.shields.io/badge/version-2.2.0-blue.svg)](https://github.com/solophp/query-builder)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

## Features

- 🚀 Fast and lightweight SQL builder with zero external dependencies
- 💪 PHP 8.2+ support with strict typing
- 🔒 Secure parameterized queries for protection against SQL injections
- 🧩 Intuitive fluent interface for building queries
- 🔄 Support for different DBMS (MySQL, PostgreSQL, SQLite) with extensibility
- ⚡️ Optional PSR-16 caching of SELECT query results (local and global control)
- 🏊‍♂️ **Connection pooling support** for high-performance applications
- 🧩 Advanced features: subqueries, raw SQL expressions, conditional queries, DISTINCT selection
- 🔄 Array handling in WHERE conditions with whereIn, orWhereIn, havingIn, and orHavingIn methods

## Installation

```bash
composer require solophp/query-builder
```

## Quick Start

### Basic Usage

```php
use Solo\QueryBuilder\Utility\QueryFactory;
use Solo\QueryBuilder\Facade\Query;

// Simple initialization using helper factory
$query = QueryFactory::createWithPdo('localhost', 'username', 'password', 'database');

// Enable global cache (ExampleCache, TTL 600 seconds)
use ExampleCache;
Query::enableCache(new ExampleCache(), 600);

// Select data
$results = $query->from('users')
    ->select('id', 'name', 'email')
    ->where('status = ?', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->getAllAssoc();

// Insert data
$insertId = $query->insert('users')
    ->values([
        'name' => 'John Doe', 
        'email' => 'john@example.com', 
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->insertGetId();

// Update data
$affectedRows = $query->update('users')
    ->set('status', 'inactive')
    ->set('updated_at', date('Y-m-d H:i:s'))
    ->where('last_login < ?', date('Y-m-d', strtotime('-6 months')))
    ->execute();

// Delete data
$affectedRows = $query->delete('users')
    ->where('id = ?', 5)
    ->execute();
```

### Connection Pooling

For high-traffic applications, use connection pooling to improve performance and manage database connections efficiently:

```php
use Solo\QueryBuilder\Utility\PooledQueryFactory;

// Create with connection pool
$query = PooledQueryFactory::createWithPool(
    host: 'localhost',
    username: 'username',
    password: 'password',
    database: 'database',
    maxConnections: 20,      // Maximum connections in pool
    minConnections: 5,       // Minimum connections to maintain  
    maxIdleTime: 3600,       // Max idle time before connection expires (seconds)
    connectionTimeout: 30    // Timeout when waiting for connection (seconds)
);

// Use exactly the same API as regular query builder
$users = $query->from('users')
    ->where('status = ?', 'active')
    ->getAllAssoc();

// Monitor pool status
$pool = $query->getConnectionPool();
if ($pool) {
    echo "Active connections: " . $pool->getActiveConnections() . "\n";
    echo "Idle connections: " . $pool->getIdleConnections() . "\n";
    echo "Total connections: " . $pool->getTotalConnections() . "\n";
}
```

#### When to Use Connection Pooling

Connection pooling is beneficial for:
- High-traffic web applications
- Applications with many concurrent database operations
- Long-running processes that perform frequent database queries
- Microservices that need to minimize connection overhead

For simple applications with low traffic, the standard single connection approach is sufficient.

## Caching (PSR-16)

This library supports caching SELECT query results via any PSR-16 cache implementation:

- **Global cache**: call `Query::enableCache($cache, $ttl)` once in your bootstrap to apply caching to all queries.
- **Per-instance cache**: chain `->withCache($cache, $ttl)` on a `Query` instance to override or set caching locally.
- **Disable cache**: use `Query::disableCache()` to turn off global caching.

```php
use Solo\QueryBuilder\Facade\Query;
use ExampleCache;

// Global cache for all Query instances (TTL 600s)
Query::enableCache(new ExampleCache(), 600);

// Local override (TTL 300s)
$query = $query->withCache(new ExampleCache(), 300);
$users = $query->from('users')->getAllAssoc();

// Disable global cache entirely
Query::disableCache();
```

## Manual Initialization

If you need more control over the initialization process, you can create all components manually:

```php
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Executors\PdoExecutor\PdoExecutor;
use Solo\QueryBuilder\Executors\PdoExecutor\Connection;
use Solo\QueryBuilder\Executors\PdoExecutor\Config;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

// Create factories
$grammarFactory = new GrammarFactory();

// Create PDO executor
$config = new Config(
    'localhost',        // host
    'username',         // username
    'password',         // password
    'database',         // database
    PDO::FETCH_ASSOC,   // fetchMode
    'mysql',            // driver
    null,               // port (optional)
    []                  // options (optional)
);

$connection = new Connection($config);
$executor = new PdoExecutor($connection);

// Creating a BuilderFactory with executor
$builderFactory = new BuilderFactory($grammarFactory, $executor, 'mysql');

// Creating a Query instance
$query = new Query($builderFactory);
```

### Manual Initialization with Connection Pool

```php
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Executors\PdoExecutor\{PooledExecutor, Config};
use Solo\QueryBuilder\Pool\ConnectionPool;
use Solo\QueryBuilder\Factory\{BuilderFactory, GrammarFactory};

$grammarFactory = new GrammarFactory();

// Create configuration
$config = new Config('localhost', 'username', 'password', 'database');

// Create connection pool
$pool = new ConnectionPool(
    config: $config,
    maxConnections: 15,
    minConnections: 3,
    maxIdleTime: 1800,
    connectionTimeout: 20
);

// Create pooled executor
$executor = new PooledExecutor($pool);

// Create query builder
$builderFactory = new BuilderFactory($grammarFactory, $executor, 'mysql');
$query = new Query($builderFactory);
```

## Building without Executing

You can also build queries without executing them:

```php
// Build a query without executing
[$sql, $bindings] = $query->from('users')
    ->select('id', 'name')
    ->where('status = ?', 'active')
    ->build();

// Now you have the SQL string and parameter bindings
echo $sql;
// SELECT `id`, `name` FROM `users` WHERE status = ?

print_r($bindings);
// ['active']
```

## Multi-DBMS Support

The library implements SQL grammar abstraction, allowing you to work with different database systems using the same API.

### Setting Default DBMS

```php
// Set MySQL as default grammar
$query->setDatabaseType('mysql');

// Set PostgreSQL as default grammar
$query->setDatabaseType('postgresql'); // or 'postgres', 'pgsql'

// Set SQLite as default grammar
$query->setDatabaseType('sqlite');
```

## SELECT Queries

### Basic Selection Operations

```php
// Select all records from table
$allUsers = $query->from('users')->getAllAssoc();

// Select specific columns
$users = $query->from('users')
    ->select('id', 'name', 'email')
    ->getAllAssoc();

// Use DISTINCT to select only unique values
$uniqueCities = $query->from('users')
    ->select('city')
    ->distinct()
    ->getAllAssoc();

// WHERE conditions
$activeUsers = $query->from('users')
    ->where('status = ?', 'active')
    ->getAllAssoc();

// Multiple conditions
$recentActiveUsers = $query->from('users')
    ->where('status = ?', 'active')
    ->where('created_at > ?', '2023-01-01')
    ->getAllAssoc();

// WHERE IN condition
$specificUsers = $query->from('users')
    ->whereIn('id', [1, 2, 3])
    ->getAllAssoc();

// OR WHERE IN condition
$usersWithRoles = $query->from('users')
    ->where('status = ?', 'active')
    ->orWhereIn('role', ['admin', 'editor'])
    ->getAllAssoc();

// Sorting
$sortedUsers = $query->from('users')
    ->orderBy('name')                // ASC by default
    ->addOrderBy('created_at', 'DESC') // additional sorting
    ->getAllAssoc();

// Limit and offset
$paginatedUsers = $query->from('users')
    ->limit(10, 100) // 10 records starting from offset 100
    ->getAllAssoc();
    
// Pagination with page
$paginatedUsers = $query->from('users')
    ->paginate(25, 1) // 25 records per page, page 1
    ->getAllAssoc();
    
// Get a single record
$user = $query->from('users')
    ->where('id = ?', 1)
    ->getAssoc();
    
// Get records as objects
$userObjects = $query->from('users')
    ->where('status = ?', 'active')
    ->getAllObj();
    
// Get a single value
$count = $query->from('users')
    ->select('COUNT(*) as count')
    ->getValue();
    
// Get an array of email addresses
$emails = $query->from('users')
    ->select('id', 'email', 'name')
    ->where('status = ?', 'active')
    ->getColumn('email');
// Result: ['john@example.com', 'jane@example.com', 'bob@example.com']

// Get an associative array of [id => name]
$userNames = $query->from('users')
    ->select('id', 'name')
    ->getColumn('name', 'id');
// Result: [1 => 'John', 2 => 'Jane', 3 => 'Bob']

// Basic HAVING
$orderStats = $query->from('orders')
    ->select('user_id', '{COUNT(*) as order_count}')
    ->groupBy('user_id')
    ->having('order_count > ?', 5)
    ->getAllAssoc();

// HAVING IN
$orderStats = $query->from('orders')
    ->select('user_id', '{COUNT(*) as order_count}')
    ->groupBy('user_id')
    ->havingIn('user_id', [1, 2, 3])
    ->getAllAssoc();

// Combined HAVING conditions
$orderStats = $query->from('orders')
    ->select('user_id', '{COUNT(*) as order_count}')
    ->groupBy('user_id')
    ->having('order_count > ?', 10)
    ->orHavingIn('user_id', [5, 6, 7])
    ->getAllAssoc();

// Get prices from products
$prices = $query->from('products')
    ->select('id', 'name', 'price')
    ->where('category_id = ?', 5)
    ->getColumn('price');
// Result: [19.99, 24.99, 14.50]

// Count the number of records
$totalUsers = $query->from('users')->count();

// Count records with conditions
$activeUserCount = $query->from('users')
    ->where('status = ?', 'active')
    ->count();

// Count specific fields or unique values
$emailCount = $query->from('users')->count('email'); // Count of non-NULL emails
$uniqueCities = $query->from('users')->count('city', true); // Count of unique cities
```

### Raw SQL Expressions

You can use raw SQL expressions by enclosing them in curly braces `{...}`:

```php
// Raw expressions in select
$users = $query->from('users')
    ->select('id', 'name', '{CONCAT(first_name, " ", last_name) as full_name}')
    ->getAllAssoc();

// Aggregation functions
$userStats = $query->from('orders')
    ->select('user_id', '{COUNT(*) as order_count}', '{SUM(amount) as total_spend}')
    ->groupBy('user_id')
    ->having('total_spend > ?', 1000)
    ->getAllAssoc();

// Date functions
$ordersByMonth = $query->from('orders')
    ->select('id', '{DATE_FORMAT(created_at, "%Y-%m") as month}', 'status')
    ->where('created_at >= ?', '2023-01-01')
    ->getAllAssoc();
```

### Conditional Queries with `when()`

The `when()` method allows you to add clauses to your query conditionally:

```php
// Only apply where clause if condition is true
$email = 'test@example.com';
$status = null;

$users = $query->from('users')
    ->when($email !== null, function($q) use ($email) {
        return $q->where('email = ?', $email);
    })
    ->when($status !== null, function($q) use ($status) {
        return $q->where('status = ?', $status);
    })
    ->getAllAssoc();

// Apply a default callback when condition is false
$minPrice = null;
$defaultMinPrice = 10;

$products = $query->from('products')
    ->when($minPrice !== null,
        function($q) use ($minPrice) {
            return $q->where('price >= ?', $minPrice);
        },
        function($q) use ($defaultMinPrice) {
            return $q->where('price >= ?', $defaultMinPrice);
        }
    )
    ->getAllAssoc();
```

## JOIN Operations

```php
// INNER JOIN
$ordersWithUsers = $query->from('orders')
    ->select('orders.id', 'orders.amount', 'users.name')
    ->join('users', 'orders.user_id = users.id')
    ->getAllAssoc();
    
// LEFT JOIN
$usersWithProfiles = $query->from('users')
    ->select('users.id', 'users.name', 'profiles.bio')
    ->leftJoin('profiles', 'users.id = profiles.user_id')
    ->getAllAssoc();

// RIGHT JOIN
$usersWithOrders = $query->from('orders')
    ->select('orders.id', 'users.name')
    ->rightJoin('users', 'orders.user_id = users.id')
    ->getAllAssoc();

// FULL JOIN
$allUsersProfiles = $query->from('users')
    ->select('users.id', 'profiles.bio')
    ->fullJoin('profiles', 'users.id = profiles.user_id')
    ->getAllAssoc();
```

### Grouping and Aggregation

```php
// GROUP BY with aggregate functions
$userOrderStats = $query->from('orders')
    ->select('user_id', '{COUNT(*) as order_count}', '{SUM(amount) as total_spend}')
    ->groupBy('user_id')
    ->having('total_spend > ?', 1000)
    ->getAllAssoc();
```

## INSERT Queries

```php
// Insert one record and get ID
$userId = $query->insert('users')
    ->values([
        'name' => 'John Doe', 
        'email' => 'john@example.com', 
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->insertGetId();

// Insert one record and get affected rows
$affectedRows = $query->insert('users')
    ->values([
        'name' => 'John Doe', 
        'email' => 'john@example.com', 
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->execute();

// Insert multiple records
$affectedRows = $query->insert('logs')
    ->values([
        ['user_id' => 1, 'action' => 'login', 'created_at' => date('Y-m-d H:i:s')],
        ['user_id' => 2, 'action' => 'logout', 'created_at' => date('Y-m-d H:i:s')]
    ])
    ->execute();
```

## UPDATE Queries

```php
// Update with array of values
$affectedRows = $query->update('users')
    ->set([
        'status' => 'inactive',
        'updated_at' => date('Y-m-d H:i:s')
    ])
    ->where('last_login < ?', date('Y-m-d', strtotime('-6 months')))
    ->execute();

// Or update by setting fields individually
$affectedRows = $query->update('users')
    ->set('status', 'inactive')
    ->set('updated_at', date('Y-m-d H:i:s'))
    ->where('id = ?', 5)
    ->execute();
```

## DELETE Queries

```php
// Delete with condition
$affectedRows = $query->delete('expired_tokens')
    ->where('expires_at < ?', date('Y-m-d H:i:s'))
    ->execute();

// Delete by ID
$affectedRows = $query->delete('users')
    ->where('id = ?', 5)
    ->execute();
```

## Checking for Records

```php
// Check if records exist
$exists = $query->from('users')
    ->where('status = ?', 'active')
    ->exists();
```

## Transaction Support

```php
try {
    $query->beginTransaction();
    
    // Perform multiple operations
    $query->insert('users')->values(['name' => 'John'])->execute();
    $query->update('stats')->set('user_count', '{user_count + 1}')->execute();
    
    $query->commit();
} catch (\Exception $e) {
    $query->rollBack();
    throw $e;
}
```

## Connection Pool Configuration

### Pool Parameters

When creating a pooled connection, you can configure various parameters:

```php
$query = PooledQueryFactory::createWithPool(
    host: 'localhost',
    username: 'user',
    password: 'password',
    database: 'mydb',
    
    // Pool Configuration
    maxConnections: 20,      // Maximum connections allowed in pool
    minConnections: 5,       // Minimum connections to maintain
    maxIdleTime: 3600,       // Seconds before idle connection expires
    connectionTimeout: 30,   // Seconds to wait for available connection
    
    // Standard PDO options
    fetchMode: PDO::FETCH_ASSOC,
    dbType: 'mysql',
    port: 3306,
    options: []
);
```

### Pool Monitoring

Monitor your connection pool status for performance optimization:

```php
$pool = $query->getConnectionPool();

if ($pool) {
    echo "Pool Statistics:\n";
    echo "- Active connections: " . $pool->getActiveConnections() . "\n";
    echo "- Idle connections: " . $pool->getIdleConnections() . "\n";
    echo "- Total connections: " . $pool->getTotalConnections() . "\n";
}
```

### Best Practices for Connection Pooling

1. **Set appropriate pool size**: Start with `maxConnections = 2 * CPU_CORES` and adjust based on your application's needs
2. **Monitor pool usage**: Regular monitoring helps identify optimal pool size
3. **Configure idle timeout**: Set `maxIdleTime` based on your application's usage patterns
4. **Handle connection timeouts**: Always handle `QueryBuilderException` when connection pool is exhausted

## API Reference

### Query Methods

| Method | Description |
|--------|-------------|
| `from(string $table)` | Sets the table to select from |
| `select(string ...$columns)` | Sets the columns to select |
| `distinct(bool $value = true)` | Enables or disables DISTINCT selection |
| `insert(string $table)` | Starts an insert query |
| `update(string $table)` | Starts an update query |
| `delete(string $table)` | Starts a delete query |
| `setDatabaseType(string $type)` | Sets the database type (mysql, postgresql, sqlite) |
| `getConnectionPool()` | Returns the connection pool instance (if using pooled connections) |

### Where Conditions

| Method | Description |
|--------|-------------|
| `where(string\|\Closure $expr, mixed ...$bindings)` | Adds a WHERE condition |
| `orWhere(string\|\Closure $expr, mixed ...$bindings)` | Adds an OR WHERE condition |
| `andWhere(string\|\Closure $expr, mixed ...$bindings)` | Adds an AND WHERE condition |
| `whereIn(string $column, array $values)` | Adds a WHERE IN condition |
| `orWhereIn(string $column, array $values)` | Adds an OR WHERE IN condition |
| `andWhereIn(string $column, array $values)` | Adds an AND WHERE IN condition |
| `when(bool $condition, callable $callback, ?callable $default = null)` | Conditionally adds clauses |

### Having Conditions

| Method | Description |
|--------|-------------|
| `having(string\|\Closure $expr, mixed ...$bindings)` | Adds a HAVING condition |
| `orHaving(string\|\Closure $expr, mixed ...$bindings)` | Adds an OR HAVING condition |
| `andHaving(string\|\Closure $expr, mixed ...$bindings)` | Adds an AND HAVING condition |
| `havingIn(string $column, array $values)` | Adds a HAVING IN condition |
| `orHavingIn(string $column, array $values)` | Adds an OR HAVING IN condition |
| `andHavingIn(string $column, array $values)` | Adds an AND HAVING IN condition |

### Joins

| Method | Description |
|--------|-------------|
| `join(string $table, string $condition, mixed ...$bindings)` | Adds an INNER JOIN |
| `leftJoin(string $table, string $condition, mixed ...$bindings)` | Adds a LEFT JOIN |
| `rightJoin(string $table, string $condition, mixed ...$bindings)` | Adds a RIGHT JOIN |
| `fullJoin(string $table, string $condition, mixed ...$bindings)` | Adds a FULL OUTER JOIN |
| `joinSub(\Closure $callback, string $alias, string $condition, mixed ...$bindings)` | Adds a subquery join |

### Clauses

| Method | Description |
|--------|-------------|
| `groupBy(string ...$cols)` | Adds a GROUP BY clause |
| `having(string\|\Closure $expr, mixed ...$bindings)` | Adds a HAVING clause |
| `orHaving(string\|\Closure $expr, mixed ...$bindings)` | Adds an OR HAVING clause |
| `orderBy(string $column, string $direction = 'ASC')` | Sets the ORDER BY clause |
| `addOrderBy(string $column, string $direction = 'ASC')` | Adds an additional ORDER BY clause |
| `limit(int $limit, ?int $offset = null)` | Adds a LIMIT clause |

### Count Methods

| Method | Description |
|--------|-------------|
| `count(?string $column = null, bool $distinct = false)` | Counts records |

### Insert Methods

| Method | Description |
|--------|-------------|
| `values(array $data)` | Sets values for insert |
| `insertGetId()` | Executes the insert and returns the last insert ID |
| `execute()` | Executes the insert and returns the number of affected rows |

### Update Methods

| Method | Description |
|--------|-------------|
| `set(string\|array $column, mixed $value = null)` | Sets the column(s) to update |
| `execute()` | Executes the update and returns the number of affected rows |

### Select Result Methods

| Method | Description |
|--------|-------------|
| `getAssoc()` | Fetches a single row as an associative array |
| `getAllAssoc()` | Fetches all rows as associative arrays |
| `getObj(string $className = 'stdClass')` | Fetches a single row as an object |
| `getAllObj(string $className = 'stdClass')` | Fetches all rows as objects |
| `getValue()` | Fetches a single value from the first column |
| `getColumn(string $column, ?string $keyColumn = null)` | Fetches an array of values from a single column |
| `paginate(int $limit, int $page = 1)` | Sets pagination with page number (using LIMIT and OFFSET) |
| `exists()` | Checks if any rows exist |
| `count(?string $column = null, bool $distinct = false)` | Counts records that match the query |
| `build()` | Returns the SQL and bindings without executing |

### Transaction Methods

| Method | Description |
|--------|-------------|
| `beginTransaction()` | Starts a new transaction |
| `commit()` | Commits the current transaction |
| `rollBack()` | Rolls back the current transaction |
| `inTransaction()` | Checks if a transaction is active |

### Connection Pool Methods

| Method | Description |
|--------|-------------|
| `getActiveConnections()` | Returns the number of connections currently in use |
| `getIdleConnections()` | Returns the number of idle connections available |
| `getTotalConnections()` | Returns the total number of connections in the pool |
| `closeAll()` | Closes all connections in the pool |

## Requirements

- PHP 8.2 or higher
- PDO Extension (for database connections)

## License

MIT