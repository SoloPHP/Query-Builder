<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Condition;

use Closure;
use Solo\QueryBuilder\Contracts\GrammarInterface;
use Solo\QueryBuilder\Utility\Raw;

final class ConditionBuilder
{
    private array $stack = [];
    private ?GrammarInterface $grammar = null;

    public function setGrammar(GrammarInterface $grammar): self
    {
        $this->grammar = $grammar;
        return $this;
    }

    public function where(string|Closure $expr, mixed ...$bindings): self
    {
        return $this->addCondition('AND', $expr, $bindings);
    }

    public function andWhere(string|Closure $expr, mixed ...$bindings): self
    {
        return $this->where($expr, ...$bindings);
    }

    public function orWhere(string|Closure $expr, mixed ...$bindings): self
    {
        return $this->addCondition('OR', $expr, $bindings);
    }

    private function addCondition(string $glue, string|Closure $expr, array $bindings): self
    {
        if ($expr instanceof Closure) {
            $nested = new self();
            if ($this->grammar) {
                $nested->setGrammar($this->grammar);
            }
            $expr($nested);
            $sql = '(' . $nested->toSql() . ')';
            $bindings = $nested->bindings();
        } else {
            $sql = $this->grammar ? $this->processIdentifiers($expr) : $expr;
        }

        $this->stack[] = ['glue' => $glue, 'expr' => $sql, 'bindings' => $bindings];
        return $this;
    }

    private function processIdentifiers(string $expr): string
    {
        if (!$this->grammar) {
            return $expr;
        }

        if (Raw::is($expr)) {
            return Raw::get($expr);
        }

        if (preg_match_all('/([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)/', $expr, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullMatch = $match[0];
                if (!$this->isInsideQuotes($expr, strpos($expr, $fullMatch))) {
                    $wrapped = $this->grammar->wrapIdentifier($fullMatch);
                    $expr = str_replace($fullMatch, $wrapped, $expr);
                }
            }
        }

        return $expr;
    }

    private function isInsideQuotes(string $expr, int $position): bool
    {
        $singleQuoteCount = substr_count(substr($expr, 0, $position), "'") -
                            substr_count(substr($expr, 0, $position), "\\'");
        $doubleQuoteCount = substr_count(substr($expr, 0, $position), '"') -
                            substr_count(substr($expr, 0, $position), '\\"');

        return ($singleQuoteCount % 2 !== 0) || ($doubleQuoteCount % 2 !== 0);
    }

    public function toSql(): string
    {
        $sql = '';
        foreach ($this->stack as $i => $item) {
            $glue = $item['glue'];
            $expr = $item['expr'];

            $sql .= ($i ? " $glue " : '') . $expr;
        }

        return $sql;
    }

    public function bindings(): array
    {
        $out = [];
        foreach ($this->stack as $item) {
            $out = array_merge($out, $item['bindings']);
        }
        return $out;
    }
}
