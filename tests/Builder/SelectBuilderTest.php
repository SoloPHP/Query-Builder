<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Builder\SelectBuilder;
use Solo\QueryBuilder\Contracts\CompilerInterface;

class SelectBuilderTest extends TestCase
{
    public function testBuildReturnsSqlAndBindings(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compileSelect')->willReturn('SQL_SELECT');
        $builder = new SelectBuilder('users', $compiler);

        $builder->where('id = ?', 1);
        [$sql, $bindings] = $builder->build();

        $this->assertSame('SQL_SELECT', $sql);
        $this->assertSame([1], $bindings);
    }

    public function testSelectColumnsAndTable(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->expects($this->once())
            ->method('compileSelect')
            ->with(
                $this->equalTo('users'),
                $this->equalTo(['id','name']),
                $this->anything()  // ignore exact clauses structure
            )
            ->willReturn('SQL_SELECT');

        $builder = new SelectBuilder('users', $compiler);
        $builder->select('id','name')->where('id = ?',1);
        [$sql, $bindings] = $builder->build();

        $this->assertSame('SQL_SELECT', $sql);
        $this->assertSame([1], $bindings);
    }
}
