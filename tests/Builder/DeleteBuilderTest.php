<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Builder\DeleteBuilder;
use Solo\QueryBuilder\Contracts\CompilerInterface;

class DeleteBuilderTest extends TestCase
{
    public function testWhereAndBuildBindings(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compileDelete')->willReturn('SQL_DELETE');
        $builder = new DeleteBuilder('users', $compiler);

        $builder->where('status = ?', 'active');
        [$sql, $bindings] = $builder->build();

        $this->assertSame('SQL_DELETE', $sql);
        $this->assertSame(['active'], $bindings);
    }

    public function testMultipleWheres(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compileDelete')->willReturn('SQL_DELETE');
        $builder = new DeleteBuilder('users', $compiler);

        $builder->where('a = ?', 1)->where('b = ?', 2);
        [$sql, $bindings] = $builder->build();

        $this->assertSame('SQL_DELETE', $sql);
        $this->assertSame([1, 2], $bindings);
    }
}
