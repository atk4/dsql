<?php

declare(strict_types=1);

namespace atk4\dsql\tests;

use atk4\core\AtkPhpunit;
use atk4\dsql\Connection;
use Doctrine\DBAL\Platforms;

abstract class DummyConnectionWithPlatform extends Connection
{
    public function getDbalPlatform(): Platforms\AbstractPlatform
    {
        throw new \atk4\dsql\Exception('Not implemented');
    }
}

class DummyConnection extends DummyConnectionWithPlatform
{
    public $driverType = 'dummy';
}

class DummyConnection2 extends DummyConnectionWithPlatform
{
    public $driverType = 'dummy2';
}

class DummyConnection3 extends DummyConnectionWithPlatform
{
    public $driverType = 'dummy3';
}

class DummyConnection4 extends DummyConnectionWithPlatform
{
    public $driverType = 'dummy4';
}

/**
 * @coversDefaultClass \atk4\dsql\Connection
 */
class ConnectionTest extends AtkPhpunit\TestCase
{
    /**
     * Test constructor.
     */
    public function testInit()
    {
        $c = Connection::connect('sqlite::memory:');
        $this->assertSame(
            '4',
            $c->expr('select (2+2)')->getOne()
        );
    }

    /**
     * Test DSN normalize.
     */
    public function testDsnNormalize()
    {
        // standard
        $dsn = Connection::normalizeDsn('mysql://root:pass@localhost/db');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => 'pass', 'driverType' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        $dsn = Connection::normalizeDsn('mysql:host=localhost;dbname=db');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => null, 'pass' => null, 'driverType' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        $dsn = Connection::normalizeDsn('mysql:host=localhost;dbname=db', 'root', 'pass');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => 'pass', 'driverType' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        // username and password should take precedence
        $dsn = Connection::normalizeDsn('mysql://root:pass@localhost/db', 'foo', 'bar');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'foo', 'pass' => 'bar', 'driverType' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        // more options
        $dsn = Connection::normalizeDsn('mysql://root:pass@localhost/db;foo=bar');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db;foo=bar', 'user' => 'root', 'pass' => 'pass', 'driverType' => 'mysql', 'rest' => 'host=localhost;dbname=db;foo=bar'], $dsn);

        // no password
        $dsn = Connection::normalizeDsn('mysql://root@localhost/db');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => null, 'driverType' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);
        $dsn = Connection::normalizeDsn('mysql://root:@localhost/db'); // see : after root
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => null, 'driverType' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        // specific DSNs
        $dsn = Connection::normalizeDsn('stopwatch:sqlite::memory');
        $this->assertSame(['dsn' => 'stopwatch:sqlite::memory', 'user' => null, 'pass' => null, 'driverType' => 'stopwatch', 'rest' => 'sqlite::memory'], $dsn);

        $dsn = Connection::normalizeDsn('sqlite::memory');
        $this->assertSame(['dsn' => 'sqlite::memory', 'user' => null, 'pass' => null, 'driverType' => 'sqlite', 'rest' => ':memory'], $dsn); // rest is unusable anyway in this context

        // with port number as URL, normalize port to ;port=1234
        $dsn = Connection::normalizeDsn('mysql://root:pass@localhost:1234/db');
        $this->assertSame(['dsn' => 'mysql:host=localhost;port=1234;dbname=db', 'user' => 'root', 'pass' => 'pass', 'driverType' => 'mysql', 'rest' => 'host=localhost;port=1234;dbname=db'], $dsn);

        // with port number as DSN, leave port as :port
        $dsn = Connection::normalizeDsn('mysql:host=localhost:1234;dbname=db');
        $this->assertSame(['dsn' => 'mysql:host=localhost:1234;dbname=db', 'user' => null, 'pass' => null, 'driverType' => 'mysql', 'rest' => 'host=localhost:1234;dbname=db'], $dsn);
    }

    public function testConnectionRegistry()
    {
        DummyConnection::registerConnectionClass();

        $this->assertSame(DummyConnection::class, Connection::resolveConnectionClass('dummy'));

        Connection::registerConnectionClass(DummyConnection2::class);
        Connection::registerConnectionClass(DummyConnection3::class);

        $this->assertSame(DummyConnection2::class, Connection::resolveConnectionClass('dummy2'));

        $this->assertSame(DummyConnection3::class, Connection::resolveConnectionClass('dummy3'));

        Connection::registerConnectionClass(DummyConnection4::class);

        $this->assertSame(DummyConnection4::class, Connection::resolveConnectionClass('dummy4'));
    }

    public function testCreatePdo()
    {
        $c1 = Connection::connect('sqlite::memory:');

        $c2 = Connection::connect($c1->connection());

        $this->assertSame($c1->connection(), $c2->connection());
    }

    public function testCreateProxy()
    {
        $driver = new class() {
            public function connection()
            {
                return 'aaa';
            }
        };

        $c = Connection::connect($driver);

        $this->assertSame(\atk4\dsql\ProxyConnection::class, get_class($c));

        $this->assertSame($c->connection(), 'aaa');
    }

    /**
     * Test driverType property.
     */
    public function testDriverType()
    {
        $c = Connection::connect('sqlite::memory:');
        $this->assertSame('sqlite', $c->driverType);

        $c = Connection::connect('stopwatch:sqlite::memory:');
        $this->assertSame('sqlite', $c->driverType);

        $c = Connection::connect('profile:sqlite::memory:');
        $this->assertSame('sqlite', $c->driverType);
        $c->callback = function () {}; // prevent output from __destruct
    }

    /**
     * Test Debug\Stopwatch\Connection.
     */
    public function testStopwatch()
    {
        $c = Connection::connect('stopwatch:sqlite::memory:');

        $result = false;
        $c->callback = function ($expr, $time, $fail) use (&$result) {
            $result = $expr->render();
        };

        $this->assertSame(
            'PDO',
            get_class($c->connection())
        );

        $this->assertSame(
            '4',
            $c->expr('select (2+2)')->getOne()
        );

        $this->assertSame(
            'select (2+2)',
            $result
        );
    }

    public function testMysqlFail()
    {
        $this->expectException(\Exception::class);
        $c = Connection::connect('mysql:host=256.256.256.256'); // invalid host
    }

    public function testStopwatchEcho()
    {
        $c = Connection::connect('stopwatch:sqlite::memory:');

        $this->assertSame(
            '4',
            $c->expr('select (2+2)')->getOne()
        );

        $this->expectOutputRegex('/select\s*\(2\s*\+\s*2\)/');
    }

    public function testProfiler()
    {
        $c = Connection::connect('profile:sqlite::memory:');

        $result = false;
        $c->callback = function ($a, $b, $c, $d, $fail) use (&$result) {
            $result = [$a, $b, $c, $d];
        };

        $this->assertSame(
            '4',
            $c->expr('select ([]+[])', [$c->expr('2'), 2])->getOne()
        );

        unset($c);
        $this->assertSame(
            [0, 0, 1, 1],
            $result
        );
    }

    public function testProfilerEcho()
    {
        $c = Connection::connect('profile:sqlite::memory:');

        $this->assertSame(
            '4',
            $c->expr('select ([]+[])', [$c->expr('2'), 2])->getOne()
        );

        $this->expectOutputString("Queries:   0, Selects:   0, Rows fetched:    1, Expressions   1\n");

        unset($c);
    }

    public function testProfiler2()
    {
        $c = Connection::connect('profile:sqlite::memory:');

        $result = false;
        $c->callback = function ($a, $b, $c, $d, $fail) use (&$result) {
            $result = [$a, $b, $c, $d];
        };

        $this->assertSame(
            '4',
            $c->dsql()->field($c->expr('2+2'))->getOne()
        );

        unset($c);
        $this->assertSame(
            [1, 1, 1, 0],
            // 1 query
            // 1 select
            // 1 result row
            // 0 expressions
            $result
        );
    }

    public function testProfiler3()
    {
        $c = Connection::connect('profile:sqlite::memory:');

        $result = false;
        $c->callback = function ($a, $b, $c, $d, $fail) use (&$result) {
            $result = [$a, $b, $c, $d];
        };

        $c->expr('create table test (id int, name varchar(255))')->execute();
        $c->dsql()->table('test')->set('name', 'John')->insert();
        $c->dsql()->table('test')->set('name', 'Peter')->insert();
        $c->dsql()->table('test')->set('name', 'Joshua')->insert();
        $res = $c->dsql()->table('test')->where('name', 'like', 'J%')->field('name')->get();

        $this->assertSame(
            [['name' => 'John'], ['name' => 'Joshua']],
            $res
        );

        unset($c);
        $this->assertSame(
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
        $this->expectException(\PDOException::class);
        $c = \atk4\dsql\Sqlite\Connection::connect(':');
    }

    public function testException2()
    {
        $this->expectException(\atk4\dsql\Exception::class);
        $c = Connection::connect('');
    }

    public function testException3()
    {
        $this->expectException(\atk4\dsql\Exception::class);
        $c = new \atk4\dsql\Sqlite\Connection('sqlite::memory');
    }

    public function testException4()
    {
        $c = new \atk4\dsql\Sqlite\Connection();
        $q = $c->expr('select (2+2)');

        $this->assertSame(
            'select (2+2)',
            $q->render()
        );

        $this->expectException(\atk4\dsql\Exception::class);
        $q->execute();
    }
}
