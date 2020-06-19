<?php

declare(strict_types=1);

namespace atk4\dsql\Oracle\Version12c;

use atk4\dsql\Oracle\Connection as BaseConnection;

/**
 * Custom Connection class specifically for Oracle 12c database.
 */
class Connection extends BaseConnection
{
    /** @var string Query classname */
    protected $query_class = Query::class;
}
