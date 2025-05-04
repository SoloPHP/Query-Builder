<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Capability;

trait WhenTrait
{
    public function when(bool $condition, callable $callback, ?callable $default = null): static
    {
        if ($condition) {
            return $callback($this);
        } elseif ($default !== null) {
            return $default($this);
        }

        return $this;
    }
}