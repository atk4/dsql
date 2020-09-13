<?php

declare(strict_types=1);

namespace atk4\dsql\tests;

use atk4\core\AtkPhpunit;

/**
 * @coversDefaultClass \atk4\dsql\Connection
 */
class OracleTest extends AtkPhpunit\TestCase
{
    public function connect($ver = '')
    {
        $version = $ver ? 'Version' . $ver . '\\' : '';

        return new \atk4\dsql\Connection(array_merge([
            'connection' => new \PDO('sqlite::memory:'),
            'query_class' => '\\atk4\\dsql\\Oracle\\' . $version . 'Query',
        ]));
    }

    public function testOracleClass()
    {
        $c = $this->connect();
        $this->assertSame(
            'select "baz" from "foo" where "bar" = :a',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->render()
        );

        $this->assertSame(
            'select "baz" "ali" from "foo" where "bar" = :a',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz', 'ali')->render()
        );
    }

    public function testClassicOracleLimit()
    {
        $c = $this->connect();
        $this->assertSame(
            'select * from (select rownum "__dsql_rownum","__t".* from (select "baz" from "foo" where "bar" = :a) "__t") where "__dsql_rownum">0 and "__dsql_rownum"<=10',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10)->render()
        );

        $this->assertSame(
            'select * from (select rownum "__dsql_rownum","__t".* from (select "baz" "baz_alias" from "foo" where "bar" = :a) "__t") where "__dsql_rownum">0 and "__dsql_rownum"<=10',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz', 'baz_alias')->limit(10)->render()
        );
    }

    public function test12OracleLimit()
    {
        $c = $this->connect('12');
        $this->assertSame(
            'select "baz" from "foo" where "bar" = :a FETCH NEXT 10 ROWS ONLY',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10)->render()
        );
    }

    public function testClassicOracleSkip()
    {
        $c = $this->connect();
        $this->assertSame(
            'select * from (select rownum "__dsql_rownum","__t".* from (select "baz" from "foo" where "bar" = :a) "__t") where "__dsql_rownum">10',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(null, 10)->render()
        );
    }

    public function test12OracleSkip()
    {
        $c = $this->connect('12');
        $this->assertSame(
            'select "baz" from "foo" where "bar" = :a OFFSET 10 ROWS',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(null, 10)->render()
        );
    }

    public function testClassicOracleLimitSkip()
    {
        $c = $this->connect();
        $this->assertSame(
            'select * from (select rownum "__dsql_rownum","__t".* from (select "baz" from "foo" where "bar" = :a) "__t") where "__dsql_rownum">99 and "__dsql_rownum"<=109',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10, 99)->render()
        );
    }

    public function test12OracleLimitSkip()
    {
        $c = $this->connect('12');
        $this->assertSame(
            'select "baz" from "foo" where "bar" = :a OFFSET 99 ROWS FETCH NEXT 10 ROWS ONLY',
            $c->dsql()->table('foo')->where('bar', 1)->field('baz')->limit(10, 99)->render()
        );
    }
}
