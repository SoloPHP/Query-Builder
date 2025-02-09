<?php

namespace Solo\QueryBuilder;

class Condition
{
    public function __construct(
        public string $operator, // 'AND', 'OR', 'ROOT'
        public string $expression,
        public array $bindings = []
    ) {}
}