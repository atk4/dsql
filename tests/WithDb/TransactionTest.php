<?php

declare(strict_types=1);

namespace atk4\dsql\tests\WithDb;

use atk4\core\AtkPhpunit;
use atk4\dsql\Connection;
use atk4\dsql\Exception;
use atk4\dsql\Expression;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;

class TransactionTest extends AtkPhpunit\TestCase
{
    /** @var Connection */
    protected $c;

    private function dropDbIfExists(): void
    {
        if ($this->c->getDatabasePlatform() instanceof OraclePlatform) {
            $this->c->connection()->executeQuery('begin
                execute immediate \'drop table "employee"\';
            exception
                when others then
                    if sqlcode != -942 then
                        raise;
                    end if;
            end;');
        } else {
            $this->c->connection()->executeQuery('DROP TABLE IF EXISTS employee');
        }
    }

    protected function setUp(): void
    {
        $this->c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        $this->dropDbIfExists();

        $strType = $this->c->getDatabasePlatform() instanceof OraclePlatform ? 'varchar2' : 'varchar';
        $boolType = ['mssql' => 'bit', 'oracle' => 'number(1)'][$this->c->getDatabasePlatform()->getName()] ?? 'bool';
        $fixIdentifiersFunc = function ($sql) {
            return preg_replace_callback('~(?:\'(?:\'\'|\\\\\'|[^\'])*\')?+\K"([^\'"()\[\]{}]*?)"~s', function ($matches) {
                if ($this->c->getDatabasePlatform() instanceof MySQLPlatform) {
                    return '`' . $matches[1] . '`';
                } elseif ($this->c->getDatabasePlatform() instanceof SQLServerPlatform) {
                    return '[' . $matches[1] . ']';
                }

                return '"' . $matches[1] . '"';
            }, $sql);
        };
        $this->c->connection()->executeQuery($fixIdentifiersFunc('CREATE TABLE "employee" ("id" int not null, "name" ' . $strType . '(100), "surname" ' . $strType . '(100), "retired" ' . $boolType . ', ' . ($this->c->getDatabasePlatform() instanceof OraclePlatform ? 'CONSTRAINT "employee_pk" ' : '') . 'PRIMARY KEY ("id"))'));
        foreach ([
            ['id' => 1, 'name' => 'Oliver', 'surname' => 'Smith', 'retired' => false],
            ['id' => 2, 'name' => 'Jack', 'surname' => 'Williams', 'retired' => true],
            ['id' => 3, 'name' => 'Harry', 'surname' => 'Taylor', 'retired' => true],
            ['id' => 4, 'name' => 'Charlie', 'surname' => 'Lee', 'retired' => false],
        ] as $row) {
            $this->c->connection()->executeQuery($fixIdentifiersFunc('INSERT INTO "employee" (' . implode(', ', array_map(function ($v) {
                return '"' . $v . '"';
            }, array_keys($row))) . ') VALUES(' . implode(', ', array_map(function ($v) {
                if (is_bool($v)) {
                    if ($this->c->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                        return $v ? 'true' : 'false';
                    }

                    return $v ? 1 : 0;
                } elseif (is_int($v) || is_float($v)) {
                    return $v;
                }

                return '\'' . $v . '\'';
            }, $row)) . ')'));
        }
    }

    protected function tearDown(): void
    {
        $this->dropDbIfExists();
    }

    private function q($table = null, $alias = null)
    {
        $q = $this->c->dsql();

        // add table to query if specified
        if ($table !== null) {
            $q->table($table, $alias);
        }

        return $q;
    }

    private function e($template = null, $args = null)
    {
        return $this->c->expr($template, $args);
    }

    public function testCommitException1()
    {
        // try to commit when not in transaction
        $this->expectException(Exception::class);
        $this->c->commit();
    }

    public function testCommitException2()
    {
        // try to commit when not in transaction anymore
        $this->c->beginTransaction();
        $this->c->commit();
        $this->expectException(Exception::class);
        $this->c->commit();
    }

    public function testRollbackException1()
    {
        // try to rollback when not in transaction
        $this->expectException(Exception::class);
        $this->c->rollBack();
    }

    public function testRollbackException2()
    {
        // try to rollback when not in transaction anymore
        $this->c->beginTransaction();
        $this->c->rollBack();
        $this->expectException(Exception::class);
        $this->c->rollBack();
    }

    /**
     * Tests simple and nested transactions.
     */
    public function testTransactions()
    {
        // truncate table, prepare
        $this->q('employee')->truncate();
        $this->assertSame(
            '0',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // without transaction, ignoring exceptions
        try {
            $this->q('employee')
                ->set(['id' => 1, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                ->insert();
            $this->q('employee')
                ->set(['id' => 2, 'FOO' => 'bar', 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
                ->insert();
        } catch (\Exception $e) {
            // ignore
        }

        $this->assertSame(
            '1',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // 1-level transaction: begin, insert, 2, rollback, 1
        $this->c->beginTransaction();
        $this->q('employee')
            ->set(['id' => 3, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
            ->insert();
        $this->assertSame(
            '2',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        $this->c->rollBack();
        $this->assertSame(
            '1',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // atomic method, rolls back everything inside atomic() callback in case of exception
        try {
            $this->c->atomic(function () {
                $this->q('employee')
                    ->set(['id' => 3, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                    ->insert();
                $this->q('employee')
                    ->set(['id' => 4, 'FOO' => 'bar', 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
                    ->insert();
            });
        } catch (\Exception $e) {
            // ignore
        }

        $this->assertSame(
            '1',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // atomic method, nested atomic transaction, rolls back everything
        try {
            $this->c->atomic(function () {
                $this->q('employee')
                    ->set(['id' => 3, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                    ->insert();

                // success, in, fail, out, fail
                $this->c->atomic(function () {
                    $this->q('employee')
                        ->set(['id' => 4, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                        ->insert();
                    $this->q('employee')
                        ->set(['id' => 5, 'FOO' => 'bar', 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
                        ->insert();
                });

                $this->q('employee')
                    ->set(['id' => 6, 'FOO' => 'bar', 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
                    ->insert();
            });
        } catch (\Exception $e) {
            // ignore
        }

        $this->assertSame(
            '1',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // atomic method, nested atomic transaction, rolls back everything
        try {
            $this->c->atomic(function () {
                $this->q('employee')
                    ->set(['id' => 3, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                    ->insert();

                // success, in, success, out, fail
                $this->c->atomic(function () {
                    $this->q('employee')
                        ->set(['id' => 4, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                        ->insert();
                });

                $this->q('employee')
                    ->set(['id' => 5, 'FOO' => 'bar', 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
                    ->insert();
            });
        } catch (\Exception $e) {
            // ignore
        }

        $this->assertSame(
            '1',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // atomic method, nested atomic transaction, rolls back everything
        try {
            $this->c->atomic(function () {
                $this->q('employee')
                    ->set(['id' => 3, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                    ->insert();

                // success, in, fail, out, catch exception
                $this->c->atomic(function () {
                    $this->q('employee')
                        ->set(['id' => 4, 'FOO' => 'bar', 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                        ->insert();
                });

                $this->q('employee')
                    ->set(['id' => 5, 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
                    ->insert();
            });
        } catch (\Exception $e) {
            // ignore
        }

        $this->assertSame(
            '1',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // atomic method, success - commit
        try {
            $this->c->atomic(function () {
                $this->q('employee')
                    ->set(['id' => 3, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
                    ->insert();
            });
        } catch (\Exception $e) {
            // ignore
        }

        $this->assertSame(
            '2',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );
    }

    /**
     * Tests inTransaction().
     */
    public function testInTransaction()
    {
        // inTransaction tests
        $this->assertFalse(
            $this->c->inTransaction()
        );

        $this->c->beginTransaction();
        $this->assertTrue(
            $this->c->inTransaction()
        );

        $this->c->rollBack();
        $this->assertFalse(
            $this->c->inTransaction()
        );

        $this->c->beginTransaction();
        $this->c->commit();
        $this->assertFalse(
            $this->c->inTransaction()
        );
    }
}
