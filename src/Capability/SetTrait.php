<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

use Solo\QueryBuilder\Clause\SetClause;

trait SetTrait
{
    use CapabilityBase;

    private array $data = [];

    public function set(string|array $column, mixed $value = null): static
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->data[$key] = $val;
            }
        } else {
            $this->data[$column] = $value;
        }

        $this->clauses = array_filter($this->clauses, function ($item) {
            return !($item['clause'] instanceof SetClause);
        });

        $assignments = [];
        $bindings = [];

        foreach ($this->data as $col => $val) {
            if (is_string($val) && $this->isRawExpression($val)) {
                $rawValue = $this->getRawContent($val);
                $assignments[] = $this->getGrammar()->wrapIdentifier($col) . " = " . $rawValue;
            } else {
                $assignments[] = $this->getGrammar()->wrapIdentifier($col) . " = ?";
                $bindings[] = $val;
            }
        }

        if (!empty($assignments)) {
            $this->addClause(
                new SetClause($assignments, $bindings),
                static::PRIORITY_SET
            );
        }

        return $this;
    }
}