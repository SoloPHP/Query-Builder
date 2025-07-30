<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the LimitTrait capability.
 */
class LimitTraitTest extends TestCase
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
     * Test basic limit functionality.
     */
    public function testBasicLimit(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->limit(10)
            ->build();

        $this->assertStringContainsString('LIMIT 10', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test limit with offset.
     */
    public function testLimitWithOffset(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->limit(10, 20)
            ->build();

        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test basic paginate functionality (page 1).
     */
    public function testPaginateFirstPage(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->paginate(15, 1)
            ->build();

        $this->assertStringContainsString('LIMIT 15 OFFSET 0', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test paginate functionality with other pages.
     */
    public function testPaginateOtherPages(): void
    {
        // Page 2 (items per page: 15)
        [$sql2, $bindings2] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->paginate(15, 2)
            ->build();

        $this->assertStringContainsString('LIMIT 15 OFFSET 15', $sql2);
        $this->assertEquals([], $bindings2);

        // Page 3 (items per page: 15)
        [$sql3, $bindings3] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->paginate(15, 3)
            ->build();

        $this->assertStringContainsString('LIMIT 15 OFFSET 30', $sql3);
        $this->assertEquals([], $bindings3);
    }

    /**
     * Test limit with filter conditions.
     */
    public function testLimitWithConditions(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->where('status = ?', 'active')
            ->limit(5)
            ->build();

        $this->assertStringContainsString('WHERE status = ?', $sql);
        $this->assertStringContainsString('LIMIT 5', $sql);
        $this->assertEquals(['active'], $bindings);
    }

    /**
     * Test paginate with sorting.
     */
    public function testPaginateWithSorting(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->paginate(10, 2)
            ->build();

        $this->assertStringContainsString('ORDER BY `name` ASC', $sql);
        $this->assertStringContainsString('LIMIT 10 OFFSET 10', $sql);
        $this->assertEquals([], $bindings);
    }
}
