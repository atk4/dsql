<?php

declare(strict_types=1);

namespace atk4\dsql\Oracle\Version12;

use atk4\dsql\Oracle\Connection as BaseConnection;

/**
 * Custom Connection class specifically for Oracle 12c database.
 */
class Connection extends BaseConnection
{
    /** @var string Query classname */
    protected $query_class = Query::class;

    protected static function connectDriver(array $dsn)
    {
        $dsn['dsn'] = str_replace('oci12:', 'oci:', $dsn['dsn']);

        return parent::connectDriver($dsn);
    }
}
