<?php
declare(strict_types=1);

namespace Solo\QueryBuilder\Builder;

use Solo\QueryBuilder\Contracts\Capability\{
    WhereCapable,
    SetCapable,
    ExecutableCapable
};
use Solo\QueryBuilder\Capability\{JoinTrait, WhereTrait, SetTrait, ExecutableTrait};

class UpdateBuilder extends AbstractBuilder implements
    WhereCapable,
    SetCapable,
    ExecutableCapable
{
    use WhereTrait;
    use JoinTrait;
    use SetTrait;
    use ExecutableTrait;

    protected function getBuilderType(): string
    {
        return 'Update';
    }
}