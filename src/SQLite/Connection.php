<?php

declare(strict_types=1);

namespace atk4\dsql\SQLite;

use atk4\dsql\Connection as BaseConnection;

/**
 * Class for establishing and maintaining connection with your SQLite database.
 */
class Connection extends BaseConnection
{
    /** @var string Query classname */
    protected $query_class = Query::class;
}
