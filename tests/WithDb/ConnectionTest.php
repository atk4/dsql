<?php

declare(strict_types=1);

namespace atk4\dsql\tests\WithDb;

use atk4\core\AtkPhpunit;
use atk4\dsql\Connection;
use atk4\dsql\Expression;

/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class ConnectionTest extends AtkPhpunit\TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testServerConnection()
    {
        $c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        return (string) $c->expr('SELECT 1' . ($c->driverType === 'oci' ? ' FROM DUAL' : ''))->getOne();
    }

    public function testGenerator()
    {
        $c = new HelloWorldConnection();
        $test = 0;
        foreach ($c->expr('abrakadabra') as $row) {
            ++$test;
        }
        $this->assertSame(10, $test);
    }
}

// @codingStandardsIgnoreStart
class HelloWorldConnection extends Connection
{
    public function execute(Expression $e)
    {
        for ($x = 0; $x < 10; ++$x) {
            yield $x => ['greeting' => 'Hello World'];
        }
    }

    // @codingStandardsIgnoreEnd
}
