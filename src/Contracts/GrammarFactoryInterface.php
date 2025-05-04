<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts;

interface GrammarFactoryInterface
{
    public function create(string $type): GrammarInterface;
}