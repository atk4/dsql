<?php

declare(strict_types=1);

namespace atk4\dsql\Sqlite;

use atk4\dsql\Connection as BaseConnection;
use Doctrine\DBAL\Platforms;

/**
 * Class for establishing and maintaining connection with your SQLite database.
 */
class Connection extends BaseConnection
{
    /** @var string Query classname */
    protected $query_class = Query::class;

    public function getDatabasePlatform(): Platforms\AbstractPlatform
    {
        return new Platforms\SqlitePlatform();
    }
}
