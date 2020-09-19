<?php

declare(strict_types=1);

namespace atk4\dsql\Mssql;

use atk4\dsql\Connection as BaseConnection;
use Doctrine\DBAL\Platforms;

/**
 * Custom Connection class specifically for MSSQL database.
 */
class Connection extends BaseConnection
{
    public $driverType = 'sqlsrv';

    /** @var string Query classname */
    protected $query_class = Query::class;

    /** @var string Expression classname */
    protected $expression_class = Expression::class;

    public function getDatabasePlatform(): Platforms\AbstractPlatform
    {
        return new Platforms\SQLServer2012Platform();
    }
}
