<?php

namespace atk4\dsql\tests;

use atk4\dsql\Connection;
use atk4\dsql\Expression;

/**
 * @ coversDefaultClass \atk4\dsql\Query
 */
class dbConnectionTest extends \atk4\core\PHPUnit_AgileTestCase
{
    public function testSQLite()
    {
        $c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        return (string) $c->expr("SELECT date('now')")->getOne();
    }

    public function testGenerator()
    {
        $c = new HelloWorldConnection();
        $test = 0;
        foreach ($c->expr('abrakadabra') as $row) {
            $test++;
        }
        $this->assertEquals(10, $test);
    }
}

// @codingStandardsIgnoreStart
class HelloWorldConnection extends Connection
{
    public function execute(Expression $e)
    {
        for ($x = 0; $x < 10; $x++) {
            yield $x => ['greeting' => 'Hello World'];
        }
    }

    // @codingStandardsIgnoreEnd
}
