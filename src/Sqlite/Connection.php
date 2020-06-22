<?php

declare(strict_types=1);

namespace atk4\dsql\Sqlite;

use atk4\dsql\Connection as BaseConnection;

/**
 * Class for establishing and maintaining connection with your SQLite database.
 */
class Connection extends BaseConnection
{
    public $driverType = 'sqlite';

    /** @var string Query classname */
    protected $query_class = Query::class;
}
