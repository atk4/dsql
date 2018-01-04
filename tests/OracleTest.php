<?php

namespace atk4\dsql\tests;

use atk4\dsql\Connection;

/**
 * @coversDefaultClass \atk4\dsql\ConnectionTest
 */
class OracleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test constructor.
     */
    public function testInit()
    {
        $c = Connection::connect('oci:dbname=mydb');
        var_dump($c->dsql()->table('foo')->where('bar', 1)->limit(10)->field('baz')->render());
    }
}
