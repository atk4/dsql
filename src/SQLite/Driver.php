<?php

namespace atk4\dsql\SQLite;

use atk4\dsql\Driver as BaseDriver;

class Driver extends BaseDriver
{
    public $type = 'sqlite';

    /** @var string Query classname */
    protected $queryClass = Query::class;
}
