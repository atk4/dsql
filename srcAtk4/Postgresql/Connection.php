<?php

declare(strict_types=1);

namespace Atk4\Dsql\Postgresql;

use atk4\dsql\Connection as BaseConnection;

/**
 * Custom Connection class specifically for PostgreSQL database.
 */
class Connection extends BaseConnection
{
    public $driverType = 'pgsql';

    /** @var string Query classname */
    protected $query_class = Query::class;
}
