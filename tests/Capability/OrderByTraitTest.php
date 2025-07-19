<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the OrderByTrait capability.
 */
class OrderByTraitTest extends TestCase
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
     * Test basic orderBy functionality with default ascending direction.
     */
    public function testBasicOrderByDefaultDirection(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->build();

        $this->assertStringContainsString('ORDER BY `name` ASC', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test orderBy with explicit direction.
     */
    public function testOrderByWithExplicitDirection(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->orderBy('created_at', 'DESC')
            ->build();

        $this->assertStringContainsString('ORDER BY `created_at` DESC', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test orderBy with incorrect direction (should default to ASC).
     */
    public function testOrderByWithIncorrectDirection(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->orderBy('id', 'INVALID_DIRECTION')
            ->build();

        $this->assertStringContainsString('ORDER BY `id` ASC', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test adding multiple orderBy clauses with addOrderBy.
     */
    public function testAddOrderBy(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name', 'email')
            ->orderBy('name', 'ASC')
            ->addOrderBy('created_at', 'DESC')
            ->build();

        $this->assertStringContainsString('ORDER BY `name` ASC', $sql);
        $this->assertStringContainsString('ORDER BY `created_at` DESC', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test that a new orderBy call overrides previous orderBy.
     */
    public function testOrderByOverridesPrevious(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->orderBy('id', 'DESC') // This should override the previous orderBy
            ->build();

        $this->assertStringContainsString('ORDER BY `id` DESC', $sql);
        $this->assertStringNotContainsString('ORDER BY `name` ASC', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test orderBy with table qualified column.
     */
    public function testOrderByWithTableQualifiedColumn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('users.id', 'users.name')
            ->orderBy('users.name', 'ASC')
            ->build();

        $this->assertStringContainsString('ORDER BY `users`.`name` ASC', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test orderBy with raw expression.
     */
    public function testOrderByWithRawExpression(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name', '{CONCAT(first_name, " ", last_name) as full_name}')
            ->orderBy('{CONCAT(first_name, " ", last_name)}', 'ASC')
            ->build();

        $this->assertStringContainsString('ORDER BY CONCAT(first_name, " ", last_name) ASC', $sql);
        $this->assertEquals([], $bindings);
    }
}
