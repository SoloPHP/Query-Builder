<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface ExecutableCapable
{
    public function execute(): int;
}