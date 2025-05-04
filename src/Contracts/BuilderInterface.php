<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

interface BuilderInterface
{
    public function build(): array;

    public function toSql(): string;

    public function getBindings(): array;
}