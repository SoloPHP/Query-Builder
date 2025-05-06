<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the WhereTrait capability.
 */
class WhereTraitTest extends TestCase
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
     * Test basic where condition with a single parameter.
     */
    public function testBasicWhereWithSingleParameter(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('id = ?', 1)
            ->build();

        $this->assertStringContainsString('WHERE id = ?', $sql);
        $this->assertEquals([1], $bindings);
    }

    /**
     * Test where condition with multiple parameters.
     */
    public function testWhereWithMultipleParameters(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('created_at BETWEEN ? AND ?', '2023-01-01', '2023-12-31')
            ->build();

        $this->assertStringContainsString('WHERE created_at BETWEEN ? AND ?', $sql);
        $this->assertEquals(['2023-01-01', '2023-12-31'], $bindings);
    }

    /**
     * Test chaining multiple where conditions with AND.
     */
    public function testMultipleWhereConditionsWithAnd(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->where('role = ?', 'admin')
            ->build();

        $this->assertStringContainsString('WHERE status = ? AND role = ?', $sql);
        $this->assertEquals(['active', 'admin'], $bindings);
    }

    /**
     * Test explicit andWhere method.
     */
    public function testExplicitAndWhere(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->andWhere('role = ?', 'admin')
            ->build();

        $this->assertStringContainsString('WHERE status = ? AND role = ?', $sql);
        $this->assertEquals(['active', 'admin'], $bindings);
    }

    /**
     * Test orWhere method.
     */
    public function testOrWhere(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->orWhere('role = ?', 'admin')
            ->build();

        $this->assertStringContainsString('WHERE status = ? OR role = ?', $sql);
        $this->assertEquals(['active', 'admin'], $bindings);
    }

    /**
     * Test chaining multiple OR conditions.
     */
    public function testMultipleOrConditions(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('id = ?', 1)
            ->orWhere('id = ?', 2)
            ->orWhere('id = ?', 3)
            ->build();

        $this->assertStringContainsString('WHERE id = ? OR id = ? OR id = ?', $sql);
        $this->assertEquals([1, 2, 3], $bindings);
    }

    /**
     * Test where with a closure for nested conditions.
     */
    public function testWhereWithClosure(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where(function($condition) {
                $condition->where('status = ?', 'active')
                    ->orWhere('role = ?', 'admin');
            })
            ->build();

        $this->assertStringContainsString('WHERE (status = ? OR role = ?)', $sql);
        $this->assertEquals(['active', 'admin'], $bindings);
    }

    /**
     * Test complex nested conditions.
     */
    public function testComplexNestedConditions(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('created_at > ?', '2023-01-01')
            ->where(function($condition) {
                $condition->where('status = ?', 'active')
                    ->orWhere(function($c) {
                        $c->where('role = ?', 'admin')
                            ->where('department = ?', 'IT');
                    });
            })
            ->build();

        $this->assertStringContainsString('WHERE created_at > ? AND (status = ? OR (role = ? AND department = ?))', $sql);
        $this->assertEquals(['2023-01-01', 'active', 'admin', 'IT'], $bindings);
    }

    /**
     * Test where with different comparison operators.
     */
    public function testWhereWithDifferentOperators(): void
    {
        // Equal
        [$sql1, $bindings1] = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->build();
        $this->assertStringContainsString('WHERE status = ?', $sql1);
        $this->assertEquals(['active'], $bindings1);

        // Not equal
        [$sql2, $bindings2] = $this->query
            ->from('users')
            ->where('status != ?', 'inactive')
            ->build();
        $this->assertStringContainsString('WHERE status != ?', $sql2);
        $this->assertEquals(['inactive'], $bindings2);

        // Greater than
        [$sql3, $bindings3] = $this->query
            ->from('users')
            ->where('age > ?', 18)
            ->build();
        $this->assertStringContainsString('WHERE age > ?', $sql3);
        $this->assertEquals([18], $bindings3);

        // Less than or equal
        [$sql4, $bindings4] = $this->query
            ->from('users')
            ->where('age <= ?', 65)
            ->build();
        $this->assertStringContainsString('WHERE age <= ?', $sql4);
        $this->assertEquals([65], $bindings4);
    }

    /**
     * Test where with LIKE operator.
     */
    public function testWhereLike(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('name LIKE ?', '%John%')
            ->build();

        $this->assertStringContainsString('WHERE name LIKE ?', $sql);
        $this->assertEquals(['%John%'], $bindings);
    }

    /**
     * Test where with IN operator.
     */
    public function testWhereIn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('id IN (?, ?, ?)', 1, 2, 3)
            ->build();

        $this->assertStringContainsString('WHERE id IN (?, ?, ?)', $sql);
        $this->assertEquals([1, 2, 3], $bindings);
    }

    /**
     * Test where with IS NULL.
     */
    public function testWhereNull(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('deleted_at IS NULL')
            ->build();

        $this->assertStringContainsString('WHERE deleted_at IS NULL', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test basic whereIn functionality.
     */
    public function testBasicWhereIn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->whereIn('id', [1, 2, 3])
            ->build();

        $this->assertStringContainsString('WHERE id IN (?, ?, ?)', $sql);
        $this->assertEquals([1, 2, 3], $bindings);
    }

    /**
     * Test whereIn with empty array.
     */
    public function testWhereInWithEmptyArray(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->whereIn('id', [])
            ->build();

        $this->assertStringNotContainsString('WHERE', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test andWhereIn method.
     */
    public function testAndWhereIn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->andWhereIn('role', ['admin', 'editor'])
            ->build();

        $this->assertStringContainsString('WHERE status = ? AND role IN (?, ?)', $sql);
        $this->assertEquals(['active', 'admin', 'editor'], $bindings);
    }

    /**
     * Test orWhereIn method.
     */
    public function testOrWhereIn(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->where('status = ?', 'active')
            ->orWhereIn('role', ['admin', 'editor'])
            ->build();

        $this->assertStringContainsString('WHERE status = ? OR role IN (?, ?)', $sql);
        $this->assertEquals(['active', 'admin', 'editor'], $bindings);
    }

    /**
     * Test combining different where methods.
     */
    public function testCombiningWhereMethods(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->whereIn('role', ['admin', 'editor'])
            ->where('active = ?', 1)
            ->orWhereIn('department', ['IT', 'HR'])
            ->build();

        $this->assertStringContainsString('WHERE role IN (?, ?) AND active = ? OR department IN (?, ?)', $sql);
        $this->assertEquals(['admin', 'editor', 1, 'IT', 'HR'], $bindings);
    }
}