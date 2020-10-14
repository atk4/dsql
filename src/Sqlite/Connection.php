<?php

declare(strict_types=1);

namespace atk4\dsql\Sqlite;

use atk4\dsql\Connection as BaseConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

/**
 * Class for establishing and maintaining connection with your SQLite database.
 */
class Connection extends BaseConnection
{
    /** @var string Query classname */
    protected $query_class = Query::class;

    public function getDatabasePlatform(): AbstractPlatform
    {
        return new SqlitePlatform();
    }
}
