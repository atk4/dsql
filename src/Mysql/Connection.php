<?php

declare(strict_types=1);

namespace atk4\dsql\Mysql;

use atk4\dsql\Connection as BaseConnection;

/**
 * Custom Connection class specifically for MySQL/MariaDB database.
 */
class Connection extends BaseConnection
{
    /** @var string Query classname */
    protected $query_class = Query::class;

    /** @var string Expression classname */
    protected $expression_class = Expression::class;
}
