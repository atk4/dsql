<?php

namespace atk4\dsql\SQLite;

use atk4\dsql\Connection;

class Driver extends Connection
{
    public $driverType = 'sqlite';

    /** @var string Query classname */
    protected $queryClass = Query::class;
}
