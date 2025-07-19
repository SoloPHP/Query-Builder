<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the WhenTrait capability.
 */
class WhenTraitTest extends TestCase
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
     * Test when method with true condition.
     */
    public function testWhenWithTrueCondition(): void
    {
        $email = 'test@example.com';

        [$sql, $bindings] = $this->query
            ->from('users')
            ->when(true, function ($query) use ($email) {
                return $query->where('email = ?', $email);
            })
            ->build();

        $this->assertStringContainsString('WHERE email = ?', $sql);
        $this->assertEquals([$email], $bindings);
    }

    /**
     * Test when method with false condition.
     */
    public function testWhenWithFalseCondition(): void
    {
        $email = 'test@example.com';

        [$sql, $bindings] = $this->query
            ->from('users')
            ->when(false, function ($query) use ($email) {
                return $query->where('email = ?', $email);
            })
            ->build();

        $this->assertStringNotContainsString('WHERE email = ?', $sql);
        $this->assertEquals([], $bindings);
    }

    /**
     * Test when method with default callback when condition is false.
     */
    public function testWhenWithDefaultCallback(): void
    {
        $email = 'test@example.com';
        $defaultEmail = 'default@example.com';

        [$sql, $bindings] = $this->query
            ->from('users')
            ->when(
                false,
                function ($query) use ($email) {
                    return $query->where('email = ?', $email);
                },
                function ($query) use ($defaultEmail) {
                    return $query->where('email = ?', $defaultEmail);
                }
            )
            ->build();

        $this->assertStringContainsString('WHERE email = ?', $sql);
        $this->assertEquals([$defaultEmail], $bindings);
    }

    /**
     * Test method chaining after when.
     */
    public function testWhenMethodChaining(): void
    {
        [$sql, $bindings] = $this->query
            ->from('users')
            ->when(true, function ($query) {
                return $query->where('active = ?', 1);
            })
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->build();

        $this->assertStringContainsString('WHERE active = ?', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('LIMIT 10', $sql);
        $this->assertEquals([1], $bindings);
    }

    /**
     * Test nested when conditions.
     */
    public function testNestedWhenConditions(): void
    {
        $status = 'active';
        $role = 'admin';

        [$sql, $bindings] = $this->query
            ->from('users')
            ->when(true, function ($query) use ($status, $role) {
                return $query->where('status = ?', $status)
                    ->when(true, function ($query) use ($role) {
                        return $query->where('role = ?', $role);
                    });
            })
            ->build();

        $this->assertStringContainsString('WHERE status = ? AND role = ?', $sql);
        $this->assertEquals([$status, $role], $bindings);
    }
}
