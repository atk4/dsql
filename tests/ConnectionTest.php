<?php

namespace atk4\dsql\tests;

use atk4\dsql\Connection;

/**
 * @coversDefaultClass \atk4\dsql\ConnectionTest
 */
class ConnectionTest extends \atk4\core\PHPUnit_AgileTestCase
{
    /**
     * Test constructor.
     */
    public function testInit()
    {
        $c = Connection::connect('sqlite::memory:');
        $this->assertEquals(
            4,
            $c->expr('select (2+2)')->getOne()
        );
    }

    /**
     * Test DSN normalize.
     */
    public function testDSNNormalize()
    {
        // standard
        $dsn = Connection::normalizeDSN('mysql://root:pass@localhost/db');
        $this->assertEquals(['dsn'=>'mysql:host=localhost;dbname=db', 'user'=>'root', 'pass'=>'pass', 'driver'=>'mysql', 'rest'=>'host=localhost;dbname=db'], $dsn);

        $dsn = Connection::normalizeDSN('mysql:host=localhost;dbname=db');
        $this->assertEquals(['dsn'=>'mysql:host=localhost;dbname=db', 'user'=>null, 'pass'=>null, 'driver'=>'mysql', 'rest'=>'host=localhost;dbname=db'], $dsn);

        $dsn = Connection::normalizeDSN('mysql:host=localhost;dbname=db', 'root', 'pass');
        $this->assertEquals(['dsn'=>'mysql:host=localhost;dbname=db', 'user'=>'root', 'pass'=>'pass', 'driver'=>'mysql', 'rest'=>'host=localhost;dbname=db'], $dsn);

        // username and password should take precedence
        $dsn = Connection::normalizeDSN('mysql://root:pass@localhost/db', 'foo', 'bar');
        $this->assertEquals(['dsn'=>'mysql:host=localhost;dbname=db', 'user'=>'foo', 'pass'=>'bar', 'driver'=>'mysql', 'rest'=>'host=localhost;dbname=db'], $dsn);

        // more options
        $dsn = Connection::normalizeDSN('mysql://root:pass@localhost/db;foo=bar');
        $this->assertEquals(['dsn'=>'mysql:host=localhost;dbname=db;foo=bar', 'user'=>'root', 'pass'=>'pass', 'driver'=>'mysql', 'rest'=>'host=localhost;dbname=db;foo=bar'], $dsn);

        // no password
        $dsn = Connection::normalizeDSN('mysql://root@localhost/db');
        $this->assertEquals(['dsn'=>'mysql:host=localhost;dbname=db', 'user'=>'root', 'pass'=>null, 'driver'=>'mysql', 'rest'=>'host=localhost;dbname=db'], $dsn);
        $dsn = Connection::normalizeDSN('mysql://root:@localhost/db'); // see : after root
        $this->assertEquals(['dsn'=>'mysql:host=localhost;dbname=db', 'user'=>'root', 'pass'=>null, 'driver'=>'mysql', 'rest'=>'host=localhost;dbname=db'], $dsn);

        // specific DSNs
        $dsn = Connection::normalizeDSN('dumper:sqlite::memory');
        $this->assertEquals(['dsn'=>'dumper:sqlite::memory', 'user'=>null, 'pass'=>null, 'driver'=>'dumper', 'rest'=>'sqlite::memory'], $dsn);

        $dsn = Connection::normalizeDSN('sqlite::memory');
        $this->assertEquals(['dsn'=>'sqlite::memory', 'user'=>null, 'pass'=>null, 'driver'=>'sqlite', 'rest'=>':memory'], $dsn); // rest is unusable anyway in this context
    }

    public function testDumper()
    {
        $c = Connection::connect('dumper:sqlite::memory:');

        $result = false;
        $c->callback = function ($expr, $time) use (&$result) {
            $result = $expr->render();
        };

        $this->assertEquals(
            'PDO',
            get_class($c->connection())
        );

        $this->assertEquals(
            4,
            $c->expr('select (2+2)')->getOne()
        );

        $this->assertEquals(
            'select (2+2)',
            $result
        );
    }

    /**
     * @expectedException Exception
     */
    public function testMysqlFail()
    {
        $c = Connection::connect('mysql:host=localhost;dbname=nosuchdb');
    }

    public function testDumperEcho()
    {
        $c = Connection::connect('dumper:sqlite::memory:');

        $this->assertEquals(
            4,
            $c->expr('select (2+2)')->getOne()
        );

        $this->expectOutputRegex("/.*select \(2\+2\).*/");
    }

    public function testCounter()
    {
        $c = Connection::connect('counter:sqlite::memory:');

        $result = false;
        $c->callback = function ($a, $b, $c, $d) use (&$result) {
            $result = [$a, $b, $c, $d];
        };

        $this->assertEquals(
            4,
            $c->expr('select ([]+[])', [$c->expr('2'), 2])->getOne()
        );

        unset($c);
        $this->assertEquals(
            [0, 0, 1, 1],
            $result
        );
    }

    public function testCounterEcho()
    {
        $c = Connection::connect('counter:sqlite::memory:');

        $this->assertEquals(
            4,
            $c->expr('select ([]+[])', [$c->expr('2'), 2])->getOne()
        );

        $this->expectOutputString("Queries:   0, Selects:   0, Rows fetched:    1, Expressions   1\n");

        unset($c);
    }

    public function testCounter2()
    {
        $c = Connection::connect('counter:sqlite::memory:');

        $result = false;
        $c->callback = function ($a, $b, $c, $d) use (&$result) {
            $result = [$a, $b, $c, $d];
        };

        $this->assertEquals(
            4,
            $c->dsql()->field($c->expr('2+2'))->getOne()
        );

        unset($c);
        $this->assertEquals(
            [1, 1, 1, 0],
            // 1 query
            // 1 select
            // 1 result row
            // 0 expressions
            $result
        );
    }

    public function testCounter3()
    {
        $c = Connection::connect('counter:sqlite::memory:');

        $result = false;
        $c->callback = function ($a, $b, $c, $d) use (&$result) {
            $result = [$a, $b, $c, $d];
        };

        $c->expr('create table test (id int, name varchar(255))')->execute();
        $c->dsql()->table('test')->set('name', 'John')->insert();
        $c->dsql()->table('test')->set('name', 'Peter')->insert();
        $c->dsql()->table('test')->set('name', 'Joshua')->insert();
        $res = $c->dsql()->table('test')->where('name', 'like', 'J%')->field('name')->get();

        $this->assertEquals(
            [['name' => 'John'], ['name' => 'Joshua']],
            $res
        );

        unset($c);
        $this->assertEquals(
            [4, 1, 2, 1],
            // 4 queries, 3 inserts and select
            // 1 select
            // 2 result row, john, joshua
            // 1 expressions, create
            $result
        );
    }

    public function testException1()
    {
        $this->setExpectedException('PDOException');
        $c = Connection::connect(':');
    }

    public function testException2()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $c = Connection::connect('');
    }

    public function testException3()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $c = new Connection('sqlite::memory');
    }

    public function testException4()
    {
        $c = new Connection();
        $q = $c->expr('select (2+2)');

        $this->assertEquals(
            'select (2+2)',
            $q->render()
        );

        $this->setExpectedException('atk4\dsql\Exception');
        $q->execute();
    }
}
