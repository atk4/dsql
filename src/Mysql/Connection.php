<?php

declare(strict_types=1);

namespace atk4\dsql\Mysql;

use atk4\dsql\Connection as BaseConnection;

class Connection extends BaseConnection
{
    protected $query_class = Query::class;
    protected $expression_class = Expression::class;
}
