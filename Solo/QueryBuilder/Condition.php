<?php

namespace Solo\QueryBuilder;

class Condition
{
    public function __construct(
        public string $operator,
        public string $expression,
        public array $bindings = []
    ) {}
}