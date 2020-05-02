<?php

declare(strict_types=1);

namespace atk4\dsql\tests\WithDb;

use atk4\core\AtkPhpunit;
use atk4\dsql\Connection;
use atk4\dsql\Exception;
use atk4\dsql\Expression;

class TransactionTest extends AtkPhpunit\TestCase
{
    /** @var Connection */
    protected $c;

    protected function setUp(): void
    {
        $this->c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        $pdo = $this->c->connection();
        $pdo->query('CREATE TEMPORARY TABLE employee (id int not null, name text, surname text, retired int, PRIMARY KEY (id))');
        $pdo->query('INSERT INTO employee (id, name, surname, retired) VALUES
                (1, \'Oliver\', \'Smith\', 1),
                (2, \'Jack\', \'Williams\', 0),
                (3, \'Harry\', \'Taylor\', 1),
                (4, \'Charlie\', \'Lee\', 0)');
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
