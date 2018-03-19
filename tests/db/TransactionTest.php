<?php

namespace atk4\dsql\tests;

use atk4\dsql\Connection;
use atk4\dsql\Expression;
use atk4\dsql\Query;

class TransactionTest extends \PHPUnit_Extensions_Database_TestCase
{
    protected $pdo;

    public function __construct()
    {
        $this->c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        $this->pdo = $this->c->connection();

        $this->pdo->query('CREATE TEMPORARY TABLE employee (id int not null, name text, surname text, retired bool, PRIMARY KEY (id))');
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->pdo, $GLOBALS['DB_DBNAME']);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/SelectTest.xml');
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

    /**
     * @expectedException Exception
     */
    public function testCommitException1()
    {
        // try to commit when not in transaction
        $this->c->commit();
    }

    /**
     * @expectedException Exception
     */
    public function testCommitException2()
    {
        // try to commit when not in transaction anymore
        $this->c->beginTransaction();
        $this->c->commit();
        $this->c->commit();
    }

    /**
     * @expectedException Exception
     */
    public function testRollbackException1()
    {
        // try to rollback when not in transaction
        $this->c->rollBack();
    }

    /**
     * @expectedException Exception
     */
    public function testRollbackException2()
    {
        // try to rollback when not in transaction anymore
        $this->c->beginTransaction();
        $this->c->rollBack();
        $this->c->rollBack();
    }

    /**
     * Tests simple and nested transactions.
     */
    public function testTransactions()
    {
        // truncate table, prepare
        $this->q('employee')->truncate();
        $this->assertEquals(
            0,
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

        $this->assertEquals(
            1,
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // 1-level transaction: begin, insert, 2, rollback, 1
        $this->c->beginTransaction();
        $this->q('employee')
            ->set(['id' => 3, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
            ->insert();
        $this->assertEquals(
            2,
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        $this->c->rollBack();
        $this->assertEquals(
            1,
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

        $this->assertEquals(
            1,
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

        $this->assertEquals(
            1,
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

        $this->assertEquals(
            1,
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

        $this->assertEquals(
            1,
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

        $this->assertEquals(
            2,
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );
    }

    /**
     * Tests inTransaction().
     */
    public function testInTransaction()
    {
        // inTransaction tests
        $this->assertEquals(
            false,
            $this->c->inTransaction()
        );

        $this->c->beginTransaction();
        $this->assertEquals(
            true,
            $this->c->inTransaction()
        );

        $this->c->rollBack();
        $this->assertEquals(
            false,
            $this->c->inTransaction()
        );

        $this->c->beginTransaction();
        $this->c->commit();
        $this->assertEquals(
            false,
            $this->c->inTransaction()
        );
    }
}
