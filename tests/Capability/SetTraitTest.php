<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the SetTrait capability.
 */
class SetTraitTest extends TestCase
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
     * Test setting a single column value.
     */
    public function testSetSingleColumnValue(): void
    {
        [$sql, $bindings] = $this->query
            ->update('users')
            ->set('name', 'John Doe')
            ->where('id = ?', 1)
            ->build();

        $this->assertStringContainsString('UPDATE `users` SET `name` = ?', $sql);
        $this->assertEquals(['John Doe', 1], $bindings);
    }

    /**
     * Test setting multiple column values with individual set calls.
     */
    public function testSetMultipleColumnValuesIndividually(): void
    {
        $timestamp = date('Y-m-d H:i:s');

        [$sql, $bindings] = $this->query
            ->update('users')
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('updated_at', $timestamp)
            ->where('id = ?', 1)
            ->build();

        $this->assertStringContainsString('UPDATE `users` SET `name` = ?, `email` = ?, `updated_at` = ?', $sql);
        $this->assertEquals(['John Doe', 'john@example.com', $timestamp, 1], $bindings);
    }

    /**
     * Test setting multiple column values with array.
     */
    public function testSetMultipleColumnValuesWithArray(): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'updated_at' => $timestamp
        ];

        [$sql, $bindings] = $this->query
            ->update('users')
            ->set($data)
            ->where('id = ?', 1)
            ->build();

        $this->assertStringContainsString('UPDATE `users` SET `name` = ?, `email` = ?, `updated_at` = ?', $sql);
        $this->assertEquals(['John Doe', 'john@example.com', $timestamp, 1], $bindings);
    }

    /**
     * Test set method with null values.
     */
    public function testSetWithNullValues(): void
    {
        [$sql, $bindings] = $this->query
            ->update('users')
            ->set('deleted_at', null)
            ->where('id = ?', 1)
            ->build();

        $this->assertStringContainsString('UPDATE `users` SET `deleted_at` = ?', $sql);
        $this->assertEquals([null, 1], $bindings);
    }

    /**
     * Test mixing individual set calls and array set calls.
     */
    public function testMixedSetCalls(): void
    {
        [$sql, $bindings] = $this->query
            ->update('users')
            ->set('name', 'John Doe')
            ->set([
                'email' => 'john@example.com',
                'status' => 'active'
            ])
            ->where('id = ?', 1)
            ->build();

        $this->assertStringContainsString('UPDATE `users` SET `name` = ?, `email` = ?, `status` = ?', $sql);
        $this->assertEquals(['John Doe', 'john@example.com', 'active', 1], $bindings);
    }

    /**
     * Test for using raw expressions in the set method.
     */
    public function testSetWithRawExpressions(): void
    {
        [$sql, $bindings] = $this->query
            ->update('users')
            ->set('name', 'John Doe')
            ->set('updated_at', '{NOW()}')
            ->set('login_count', '{login_count + 1}')
            ->where('id = ?', 1)
            ->build();
        $this->assertStringContainsString('UPDATE `users` SET `name` = ?, `updated_at` = NOW(), `login_count` = login_count + 1', $sql);
        // Check that only the value for name and the where condition are in bindings
        $this->assertEquals(['John Doe', 1], $bindings);
    }

    /**
     * Test for using raw expressions in the set method with an array of values.
     */
    public function testSetArrayWithRawExpressions(): void
    {
        [$sql, $bindings] = $this->query
            ->update('users')
            ->set([
                'name' => 'John Doe',
                'updated_at' => '{NOW()}',
                'login_count' => '{login_count + 1}'
            ])
            ->where('id = ?', 1)
            ->build();
        $this->assertStringContainsString('UPDATE `users` SET `name` = ?, `updated_at` = NOW(), `login_count` = login_count + 1', $sql);
        // Check that only the value for name and the where condition are in bindings
        $this->assertEquals(['John Doe', 1], $bindings);
    }

    /**
     * Test for mixed usage of regular values and raw expressions.
     */
    public function testMixedSetWithRawAndRegularValues(): void
    {
        $timestamp = date('Y-m-d H:i:s');

        [$sql, $bindings] = $this->query
            ->update('products')
            ->set('name', 'New Product')
            ->set('price', 19.99)
            ->set('stock', '{stock - 1}')
            ->set('last_update', $timestamp)
            ->set('status', '{IF(stock > 0, "available", "out_of_stock")}')
            ->where('id = ?', 5)
            ->build();
        $this->assertStringContainsString('UPDATE `products` SET `name` = ?, `price` = ?, `stock` = stock - 1, `last_update` = ?, `status` = IF(stock > 0, "available", "out_of_stock")', $sql);
        // Only regular values should be in bindings
        $this->assertEquals(['New Product', 19.99, $timestamp, 5], $bindings);
    }
}
