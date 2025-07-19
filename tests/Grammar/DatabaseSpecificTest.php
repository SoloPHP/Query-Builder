<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Grammar;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

class DatabaseSpecificTest extends TestCase
{
    private Query $query;

    protected function setUp(): void
    {
        $mockExecutor = $this->createMock(ExecutorInterface::class);
        $grammarFactory = new GrammarFactory();
        $builderFactory = new BuilderFactory($grammarFactory, $mockExecutor, 'mysql');
        $this->query = new Query($builderFactory);
    }

    /**
     * Test the exact match of SQL queries for different dialects.
     */
    public function testDialectSpecificSQL(): void
    {
        // MySQL
        $this->query->setDatabaseType('mysql');
        [$mysqlSql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->where('status = ?', 'active')
            ->build();

        $this->assertEquals("SELECT `id`, `name` FROM `users` WHERE status = ?", $mysqlSql);
        $this->assertEquals(['active'], $bindings);

        // PostgreSQL
        $this->query->setDatabaseType('postgresql');
        [$pgsqlSql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->where('status = ?', 'active')
            ->build();

        $this->assertEquals('SELECT "id", "name" FROM "users" WHERE status = ?', $pgsqlSql);
        $this->assertEquals(['active'], $bindings);

        // SQLite
        $this->query->setDatabaseType('sqlite');
        [$sqliteSql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->where('status = ?', 'active')
            ->build();

        $this->assertEquals('SELECT "id", "name" FROM "users" WHERE status = ?', $sqliteSql);
        $this->assertEquals(['active'], $bindings);
    }

    /**
     * Test identifier quoting across different database types.
     */
    public function testIdentifierQuoting(): void
    {
        // MySQL uses backticks
        $this->query->setDatabaseType('mysql');
        [$mysqlSql, $bindings] = $this->query
            ->from('users u')
            ->select('u.id', 'u.name', 'u.email')
            ->join('orders o', 'u.id = o.user_id')
            ->build();

        $this->assertStringContainsString('SELECT `u`.`id`, `u`.`name`, `u`.`email`', $mysqlSql);
        $this->assertStringContainsString('FROM `users`', $mysqlSql);
        $this->assertStringContainsString('ON `u`.`id` = `o`.`user_id`', $mysqlSql);

        // PostgreSQL uses double quotes
        $this->query->setDatabaseType('postgresql');
        [$pgsqlSql, $bindings] = $this->query
            ->from('users u')
            ->select('u.id', 'u.name', 'u.email')
            ->join('orders o', 'u.id = o.user_id')
            ->build();

        $this->assertStringContainsString('SELECT "u"."id", "u"."name", "u"."email"', $pgsqlSql);
        $this->assertStringContainsString('FROM "users"', $pgsqlSql);
        $this->assertStringContainsString('ON "u"."id" = "o"."user_id"', $pgsqlSql);
    }
}
