<?php
namespace atk4\dsql\tests;

use atk4\dsql\Query;
use atk4\dsql\Expression;
use atk4\dsql\Connection;

/**
 * @ coversDefaultClass \atk4\dsql\Query
 */
class dbConnectionTest extends \PHPUnit_Framework_TestCase
{
    function testSQLite() {

        $c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        return (string)$c->expr("SELECT date('now')")->getOne();
    }
}
