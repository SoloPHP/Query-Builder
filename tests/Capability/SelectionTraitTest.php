<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the SelectionTrait capability.
 */
class SelectionTraitTest extends TestCase
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
     * Test selecting all columns (default).
     */
    public function testSelectAll(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->build();

        $this->assertStringContainsString('SELECT * FROM `users`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test selecting specific columns.
     */
    public function testSelectSpecificColumns(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name', 'email')
            ->build();

        $this->assertStringContainsString('SELECT `id`, `name`, `email` FROM `users`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test selecting columns with table qualifier.
     */
    public function testSelectColumnsWithTableQualifier(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('users.id', 'users.name', 'users.email')
            ->build();

        $this->assertStringContainsString('SELECT `users`.`id`, `users`.`name`, `users`.`email` FROM `users`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test selecting columns with aliases.
     */
    public function testSelectColumnsWithAliases(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name as user_name', 'email')
            ->build();

        $this->assertStringContainsString('SELECT `id`, `name` AS `user_name`, `email` FROM `users`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test selecting columns with raw expressions.
     */
    public function testSelectWithRawExpressions(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', '{COUNT(*) as user_count}', '{CONCAT(first_name, " ", last_name) as full_name}')
            ->build();

        $this->assertStringContainsString('SELECT `id`, COUNT(*) as user_count, CONCAT(first_name, " ", last_name) as full_name FROM `users`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test selecting a specific table.
     */
    public function testFromSpecificTable(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->build();

        $this->assertStringContainsString('FROM `users`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test selecting from a table with an alias.
     */
    public function testFromTableWithAlias(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users u')
            ->select('u.id', 'u.name')
            ->build();

        $this->assertStringContainsString('FROM `users` AS `u`', $sql);
        $this->assertStringContainsString('SELECT `u`.`id`, `u`.`name`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test from method with explicit AS keyword.
     */
    public function testFromWithExplicitAsKeyword(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users AS u')
            ->select('u.id', 'u.name')
            ->build();

        $this->assertStringContainsString('FROM `users` AS `u`', $sql);
        $this->assertStringContainsString('SELECT `u`.`id`, `u`.`name`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test that select method can be chained from the facade.
     */
    public function testSelectMethodChaining(): void
    {
        [$sql, $bindings] = $this->query
            ->select('id', 'name', 'email')
            ->from('users')
            ->build();

        $this->assertStringContainsString('SELECT `id`, `name`, `email` FROM `users`', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test invalid case (empty from).
     */
    public function testInvalidEmptyFrom(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Missing from method
        $this->query
            ->select('id', 'name')
            ->build();
    }
}