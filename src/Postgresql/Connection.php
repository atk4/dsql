<?php

declare(strict_types=1);

namespace atk4\dsql\Postgresql;

use atk4\dsql\Connection as BaseConnection;
use Doctrine\DBAL\Platforms;

/**
 * Custom Connection class specifically for PostgreSQL database.
 */
class Connection extends BaseConnection
{
    /** @var string Query classname */
    protected $query_class = Query::class;

    public function getDatabasePlatform(): Platforms\AbstractPlatform
    {
        return new Platforms\PostgreSQL100Platform();
    }
}
