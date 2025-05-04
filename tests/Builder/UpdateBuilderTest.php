<?php declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Builder\UpdateBuilder;
use Solo\QueryBuilder\Contracts\CompilerInterface;

class UpdateBuilderTest extends TestCase
{
    public function testSetAndBuildBindings(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compileUpdate')->willReturn('SQL_UPDATE');
        $builder = new UpdateBuilder('users', $compiler);

        $builder->set('name','Alice')->set('age',20);
        [$sql, $bindings] = $builder->build();

        $this->assertSame('SQL_UPDATE', $sql);
        $this->assertSame(['Alice',20], $bindings);
    }

    public function testWhereAddsBindings(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compileUpdate')->willReturn('SQL_UPDATE');
        $builder = new UpdateBuilder('users', $compiler);

        $builder->set('name','Alice')->set('age',20)->where('id = ?',5);
        [$sql, $bindings] = $builder->build();

        $this->assertSame('SQL_UPDATE', $sql);
        $this->assertSame(['Alice',20,5], $bindings);
    }
}
