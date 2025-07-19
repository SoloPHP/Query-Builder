<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Contracts\Capability;

interface InsertGetIdCapable extends ExecutableCapable
{
    public function insertGetId(): int|string|null;
}
