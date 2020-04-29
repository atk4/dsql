<?php

namespace atk4\dsql\MySQL;

use atk4\dsql\Connection as BaseConnection;

class Connection extends BaseConnection
{
    const DEFAULT_DRIVER_TYPE = 'mysql';
    
    protected $queryClass = Query::class;

    protected $expressionClass = Expression::class;
}
