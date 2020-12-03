<?php

declare(strict_types=1);

namespace Atk4\Dsql\Tests\WithDb;

use Atk4\Core\AtkPhpunit;
use Atk4\Dsql\Connection;
use Doctrine\DBAL\Platforms\OraclePlatform;

/**
 * @coversDefaultClass \Atk4\Dsql\Query
 */
class ConnectionTest extends AtkPhpunit\TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testServerConnection()
    {
        $c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        return (string) $c->expr('SELECT 1' . ($c->getDatabasePlatform() instanceof OraclePlatform ? ' FROM DUAL' : ''))->getOne();
    }
}
