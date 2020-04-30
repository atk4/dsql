<?php

namespace atk4\dsql\MySQL;

use atk4\dsql\Connection;

class Driver extends Connection
{
    public $driverType = 'mysql';

    protected $queryClass = Query::class;

    protected $expressionClass = Expression::class;
}
