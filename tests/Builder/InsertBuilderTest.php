<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Solo\QueryBuilder\Builder\InsertBuilder;
use Solo\QueryBuilder\Contracts\CompilerInterface;

class InsertBuilderTest extends TestCase
{
    public function testValuesAndBuildBindingsSingleRow(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compileInsert')->willReturn('SQL_INSERT');
        $builder = new InsertBuilder('users', $compiler);

        $builder->values(['name' => 'John','age' => 30]);
        [$sql, $bindings] = $builder->build();

        $this->assertSame('SQL_INSERT', $sql);
        $this->assertSame(['John',30], $bindings);
    }

    public function testValuesAndBuildBindingsMultipleRows(): void
    {
        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compileInsert')->willReturn('SQL_INSERT');
        $builder = new InsertBuilder('users', $compiler);

        $builder->values([
            ['name' => 'John','age' => 30],
            ['name' => 'Jane','age' => 25]
        ]);
        [$sql, $bindings] = $builder->build();

        // Multiple rows should produce bindings for all rows in sequence
        $this->assertSame('SQL_INSERT', $sql);
        $this->assertSame(['John',30,'Jane',25], $bindings);
    }
}
