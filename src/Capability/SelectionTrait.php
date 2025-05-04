<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait SelectionTrait
{
    protected array $columns = ['*'];
    protected string $table = '';
    protected bool $distinct = false;

    public function select(string ...$cols): static
    {
        $this->columns = $cols;
        return $this;
    }

    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function distinct(bool $value = true): static
    {
        $this->distinct = $value;
        return $this;
    }
}