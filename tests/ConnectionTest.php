<?php

declare(strict_types=1);

namespace atk4\dsql\Tests;

use atk4\core\AtkPhpunit;
use atk4\dsql\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

class DummyConnection extends Connection
{
    public function getDatabasePlatform(): AbstractPlatform
    {
        return new class() extends SqlitePlatform {
            public function getName()
            {
                return 'dummy';
            }
        };
    }
}

class DummyConnection2 extends Connection
{
    public function getDatabasePlatform(): AbstractPlatform
    {
        return new class() extends SqlitePlatform {
            public function getName()
            {
                return 'dummy2';
            }
        };
    }
}

class DummyConnection3 extends Connection
{
    public function getDatabasePlatform(): AbstractPlatform
    {
        return new class() extends SqlitePlatform {
            public function getName()
            {
                return 'dummy3';
            }
        };
    }
}

class DummyConnection4 extends Connection
{
    public function getDatabasePlatform(): AbstractPlatform
    {
        return new class() extends SqlitePlatform {
            public function getName()
            {
                return 'dummy4';
            }
        };
    }
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
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => 'pass', 'driverSchema' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        $dsn = Connection::normalizeDsn('mysql:host=localhost;dbname=db');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => null, 'pass' => null, 'driverSchema' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        $dsn = Connection::normalizeDsn('mysql:host=localhost;dbname=db', 'root', 'pass');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => 'pass', 'driverSchema' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        // username and password should take precedence
        $dsn = Connection::normalizeDsn('mysql://root:pass@localhost/db', 'foo', 'bar');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'foo', 'pass' => 'bar', 'driverSchema' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        // more options
        $dsn = Connection::normalizeDsn('mysql://root:pass@localhost/db;foo=bar');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db;foo=bar', 'user' => 'root', 'pass' => 'pass', 'driverSchema' => 'mysql', 'rest' => 'host=localhost;dbname=db;foo=bar'], $dsn);

        // no password
        $dsn = Connection::normalizeDsn('mysql://root@localhost/db');
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => null, 'driverSchema' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);
        $dsn = Connection::normalizeDsn('mysql://root:@localhost/db'); // see : after root
        $this->assertSame(['dsn' => 'mysql:host=localhost;dbname=db', 'user' => 'root', 'pass' => null, 'driverSchema' => 'mysql', 'rest' => 'host=localhost;dbname=db'], $dsn);

        $dsn = Connection::normalizeDsn('sqlite::memory');
        $this->assertSame(['dsn' => 'sqlite::memory', 'user' => null, 'pass' => null, 'driverSchema' => 'sqlite', 'rest' => ':memory'], $dsn); // rest is unusable anyway in this context

        // with port number as URL, normalize port to ;port=1234
        $dsn = Connection::normalizeDsn('mysql://root:pass@localhost:1234/db');
        $this->assertSame(['dsn' => 'mysql:host=localhost;port=1234;dbname=db', 'user' => 'root', 'pass' => 'pass', 'driverSchema' => 'mysql', 'rest' => 'host=localhost;port=1234;dbname=db'], $dsn);

        // with port number as DSN, leave port as :port
        $dsn = Connection::normalizeDsn('mysql:host=localhost:1234;dbname=db');
        $this->assertSame(['dsn' => 'mysql:host=localhost:1234;dbname=db', 'user' => null, 'pass' => null, 'driverSchema' => 'mysql', 'rest' => 'host=localhost:1234;dbname=db'], $dsn);
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

    public function testMysqlFail()
    {
        $this->expectException(\Exception::class);
        $c = Connection::connect('mysql:host=256.256.256.256'); // invalid host
    }

    public function testException1()
    {
        $this->expectException(\atk4\dsql\Exception::class);
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
