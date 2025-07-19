<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;
use Solo\QueryBuilder\Exception\QueryBuilderException;

/**
 * Tests for the ValuesTrait capability.
 */
class ValuesTraitTest extends TestCase
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
     * Test inserting a single row.
     */
    public function testInsertSingleRow(): void
    {
        [$sql, $bindings] = $this->query
            ->insert('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'created_at' => '2023-01-01 10:00:00'
            ])
            ->build();

        $this->assertStringContainsString('INSERT INTO `users`', $sql);
        $this->assertStringContainsString('(`name`, `email`, `created_at`)', $sql);
        $this->assertStringContainsString('VALUES (?, ?, ?)', $sql);
        $this->assertEquals(['John Doe', 'john@example.com', '2023-01-01 10:00:00'], $bindings);
    }

    /**
     * Test inserting multiple rows.
     */
    public function testInsertMultipleRows(): void
    {
        [$sql, $bindings] = $this->query
            ->insert('logs')
            ->values([
                ['user_id' => 1, 'action' => 'login', 'created_at' => '2023-01-01 10:00:00'],
                ['user_id' => 2, 'action' => 'logout', 'created_at' => '2023-01-01 11:00:00']
            ])
            ->build();

        $this->assertStringContainsString('INSERT INTO `logs`', $sql);
        $this->assertStringContainsString('(`user_id`, `action`, `created_at`)', $sql);
        $this->assertStringContainsString('VALUES (?, ?, ?), (?, ?, ?)', $sql);
        $this->assertEquals([1, 'login', '2023-01-01 10:00:00', 2, 'logout', '2023-01-01 11:00:00'], $bindings);
    }

    /**
     * Test inserting multiple rows with null values.
     */
    public function testInsertWithNullValues(): void
    {
        [$sql, $bindings] = $this->query
            ->insert('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com', 'phone' => null],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'phone' => '555-1234']
            ])
            ->build();

        $this->assertStringContainsString('INSERT INTO `users`', $sql);
        $this->assertStringContainsString('(`name`, `email`, `phone`)', $sql);
        $this->assertStringContainsString('VALUES (?, ?, ?), (?, ?, ?)', $sql);
        $this->assertEquals(['John', 'john@example.com', null, 'Jane', 'jane@example.com', '555-1234'], $bindings);
    }

    /**
     * Test that an exception is thrown when rows have different columns.
     */
    public function testExceptionOnDifferentColumnSets(): void
    {
        $this->expectException(QueryBuilderException::class);

        $this->query
            ->insert('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'phone' => '555-1234'] // Different column set
            ])
            ->build();
    }

    /**
     * Test that an exception is thrown when columns have different order.
     */
    public function testExceptionOnDifferentColumnOrder(): void
    {
        $this->expectException(QueryBuilderException::class);

        $this->query
            ->insert('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com'],
                ['email' => 'jane@example.com', 'name' => 'Jane'] // Different column order
            ])
            ->build();
    }

    /**
     * Test adding values with numeric keys.
     */
    public function testValuesWithNumericKeys(): void
    {
        [$sql, $bindings] = $this->query
            ->insert('positions')
            ->values([
                ['x' => 10, 'y' => 20, 'z' => 30],
                ['x' => 5, 'y' => 15, 'z' => 25]
            ])
            ->build();

        $this->assertStringContainsString('INSERT INTO `positions`', $sql);
        $this->assertStringContainsString('(`x`, `y`, `z`)', $sql);
        $this->assertStringContainsString('VALUES (?, ?, ?), (?, ?, ?)', $sql);
        $this->assertEquals([10, 20, 30, 5, 15, 25], $bindings);
    }
}
