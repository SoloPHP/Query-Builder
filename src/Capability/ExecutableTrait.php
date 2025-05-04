<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait ExecutableTrait
{
    abstract public function build(): array;

    public function execute(): int
    {
        if (!$this->executor) {
            throw new \RuntimeException('No executor available to execute the query');
        }

        [$sql, $bindings] = $this->build();
        $this->executor->query($sql, $bindings);
        return $this->executor->rowCount();
    }
}