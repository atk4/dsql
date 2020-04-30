<?php

namespace atk4\dsql\SQLite;

use atk4\dsql\Connection as BaseConnection;

class Connection extends BaseConnection
{
    public $driverType = 'sqlite';

    /** @var string Query classname */
    protected $queryClass = Query::class;
}
