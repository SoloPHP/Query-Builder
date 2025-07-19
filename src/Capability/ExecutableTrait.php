<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait ExecutableTrait
{
    abstract public function build(): array;
    abstract protected function validateExecutor(): void;

    public function execute(): int
    {
        $this->validateExecutor();
        [$sql, $bindings] = $this->build();
        $this->executor->query($sql, $bindings);
        return $this->executor->rowCount();
    }
}
