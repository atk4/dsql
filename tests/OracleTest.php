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
    public function testDetection()
    {
        try {
            $c = Connection::connect('oci:dbname=mydb');
            $this->assertEquals(
                'select "baz" from "foo" where "bar" = :a',
                $c->dsql()->table('foo')->where('bar', 1)->field('baz')->render()
            );
        } catch (\PDOException $e) {
            if (!extension_loaded('oci8')) {
                $this->markTestSkipped('The oci8 extension is not available.');
            }

            throw $e;
        }
    }

    public function connect($ver = '')
    {
        return new \atk4\dsql\Connection(array_merge([
            'connection'       => new \PDO('sqlite::memory:'),
            'query_class'      => 'atk4\dsql\Query_Oracle'.$ver,
            'expression_class' => 'atk4\dsql\Expression_Oracle',
        ]));
    }

    public function testOracleClass()
    {
        $c = $this->connect();
        $this->assertEquals(
            'select "baz" from "foo" where "bar" = :a',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->render()
        );

        $this->assertEquals(
            'select "baz" "ali" from "foo" where "bar" = :a',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz', 'ali')->render()
        );
    }

    public function testClassicOracleLimit()
    {
        $c = $this->connect();
        $this->assertEquals(
            'select "baz" from (select rownum "__dsql_rownum", "baz" from "foo" where "bar" = :a) where "__dsql_rownum">0 and "__dsql_rownum"<=10',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10)->render()
        );

        $this->assertEquals(
            'select "baz" "baz_alias" from (select rownum "__dsql_rownum", "baz" from "foo" where "bar" = :a) where "__dsql_rownum">0 and "__dsql_rownum"<=10',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz', 'baz_alias')->limit(10)->render()
        );
    }

    public function test12cOracleLimit()
    {
        $c = $this->connect('12c');
        $this->assertEquals(
            'select "baz" from "foo" where "bar" = :a FETCH NEXT 10 ROWS ONLY',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10)->render()
        );
    }

    public function testClassicOracleSkip()
    {
        $c = $this->connect();
        $this->assertEquals(
            'select "baz" from (select rownum "__dsql_rownum", "baz" from "foo" where "bar" = :a) where "__dsql_rownum">10',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(null, 10)->render()
        );
    }

    public function test12cOracleSkip()
    {
        $c = $this->connect('12c');
        $this->assertEquals(
            'select "baz" from "foo" where "bar" = :a OFFSET 10 ROWS',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(null, 10)->render()
        );
    }

    public function testClassicOracleLimitSkip()
    {
        $c = $this->connect();
        $this->assertEquals(
            'select "baz" from (select rownum "__dsql_rownum", "baz" from "foo" where "bar" = :a) where "__dsql_rownum">99 and "__dsql_rownum"<=109',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10, 99)->render()
        );
    }

    public function test12cOracleLimitSkip()
    {
        $c = $this->connect('12c');
        $this->assertEquals(
            'select "baz" from "foo" where "bar" = :a OFFSET 99 ROWS FETCH NEXT 10 ROWS ONLY',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10, 99)->render()
        );
    }
}
