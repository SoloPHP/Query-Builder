<?php

declare(strict_types=1);

namespace Solo\QueryBuilder\Enum;

enum ClausePriority: int
{
    case JOIN = 10;
    case SET = 15;
    case WHERE = 20;
    case GROUP_BY = 30;
    case HAVING = 40;
    case ORDER_BY = 50;
    case LIMIT = 60;
    case VALUES = 70;
}
