<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Capability;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Contracts\ExecutorInterface;
use Solo\QueryBuilder\Facade\Query;
use Solo\QueryBuilder\Factory\BuilderFactory;
use Solo\QueryBuilder\Factory\GrammarFactory;

/**
 * Tests for the InsertGetIdTrait capability.
 */
class InsertGetIdTraitTest extends TestCase
{
    private Query $query;
    private ExecutorInterface $mockExecutor;

    protected function setUp(): void
    {
        $this->mockExecutor = $this->createMock(ExecutorInterface::class);
        $grammarFactory = new GrammarFactory();
        $builderFactory = new BuilderFactory($grammarFactory, $this->mockExecutor, 'mysql');
        $this->query = new Query($builderFactory);
    }

    /**
     * Test insertGetId method with numeric ID.
     */
    public function testInsertGetIdNumeric(): void
    {
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function ($sql) {
                    return strpos($sql, 'INSERT INTO `users`') !== false;
                }),
                $this->equalTo(['John Doe', 'john@example.com'])
            )
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('42');

        $id = $this->query
            ->insert('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ])
            ->insertGetId();

        $this->assertEquals(42, $id);
        $this->assertIsInt($id);
    }

    /**
     * Test insertGetId method with string ID.
     */
    public function testInsertGetIdString(): void
    {
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function ($sql) {
                    return strpos($sql, 'INSERT INTO `users`') !== false;
                }),
                $this->equalTo(['John Doe', 'john@example.com'])
            )
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('UUID-123');

        $id = $this->query
            ->insert('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ])
            ->insertGetId();

        $this->assertEquals('UUID-123', $id);
        $this->assertIsString($id);
    }

    /**
     * Test insertGetId method with false return from lastInsertId.
     */
    public function testInsertGetIdFalse(): void
    {
        $this->mockExecutor->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(function ($sql) {
                    return strpos($sql, 'INSERT INTO `users`') !== false;
                }),
                $this->equalTo(['John Doe', 'john@example.com'])
            )
            ->willReturnSelf();

        $this->mockExecutor->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(false);

        $id = $this->query
            ->insert('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ])
            ->insertGetId();

        $this->assertNull($id);
    }

    /**
     * Test exception when no executor is available.
     */
    public function testExceptionWhenNoExecutor(): void
    {
        // Create a query without an executor
        $grammarFactory = new GrammarFactory();
        $compiler = new \Solo\QueryBuilder\Compiler\SqlCompiler($grammarFactory->create('mysql'));
        $builder = new \Solo\QueryBuilder\Builder\InsertBuilder('users', $compiler); // No executor passed

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No executor available to execute the query');

        $builder
            ->values(['name' => 'John Doe'])
            ->insertGetId();
    }
}
