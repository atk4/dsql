<?php

namespace atk4\dsql\MySQL;

use atk4\dsql\Driver as BaseDriver;

class Driver extends BaseDriver
{
    public $type = 'mysql';

    protected $queryClass = Query::class;

    protected $expressionClass = Expression::class;
}
