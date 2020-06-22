<?php

declare(strict_types=1);

namespace atk4\dsql\tests\WithDb;

use atk4\core\AtkPhpunit;
use atk4\dsql\Connection;
use atk4\dsql\Exception;
use atk4\dsql\Expression;

class SelectTest extends AtkPhpunit\TestCase
{
    /** @var Connection */
    protected $c;

    protected function setUp(): void
    {
        $this->c = Connection::connect($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        $pdo = $this->c->connection();
        $pdo->query('CREATE TEMPORARY TABLE employee (id int not null, name text, surname text, retired bool, PRIMARY KEY (id))');
        $pdo->query('INSERT INTO employee (id, name, surname, retired) VALUES
                (1, \'Oliver\', \'Smith\', ' . ($this->c->driverType === 'pgsql' ? 'false' : '0') . '),
                (2, \'Jack\', \'Williams\', ' . ($this->c->driverType === 'pgsql' ? 'true' : '1') . '),
                (3, \'Harry\', \'Taylor\', ' . ($this->c->driverType === 'pgsql' ? 'true' : '1') . '),
                (4, \'Charlie\', \'Lee\', ' . ($this->c->driverType === 'pgsql' ? 'false' : '0') . ')');
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

    public function testBasicQueries()
    {
        $this->assertSame(4, count($this->q('employee')->get()));

        $this->assertSame(
            ['name' => 'Oliver', 'surname' => 'Smith'],
            $this->q('employee')->field('name,surname')->getRow()
        );

        $this->assertSame(
            ['surname' => 'Williams'],
            $this->q('employee')->field('surname')->where('retired', '1')->getRow()
        );

        $this->assertSame(
            '4',
            $this->q()->field(new Expression('2+2'))->getOne()
        );

        $this->assertSame(
            '4',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        $names = [];
        foreach ($this->q('employee')->where('retired', false) as $row) {
            $names[] = $row['name'];
        }

        $this->assertSame(
            ['Oliver', 'Charlie'],
            $names
        );

        $this->assertSame(
            [['now' => '4']],
            $this->q()->field(new Expression('2+2'), 'now')->get()
        );

        /*
         * PostgreSQL needs to have values cast, to make the query work.
         * But CAST(.. AS int) does not work in mysql. So we use two different tests..
         * (CAST(.. AS int) will work on mariaDB, whereas mysql needs it to be CAST(.. AS signed))
         */
        if ($this->c->driverType === 'pgsql') {
            $this->assertSame(
                [['now' => '6']],
                $this->q()->field(new Expression('CAST([] AS int)+CAST([] AS int)', [3, 3]), 'now')->get()
            );
        } else {
            $this->assertSame(
                [['now' => '6']],
                $this->q()->field(new Expression('[]+[]', [3, 3]), 'now')->get()
            );
        }

        $this->assertSame(
            '5',
            $this->q()->field(new Expression('COALESCE([],5)', [null]), 'null_test')->getOne()
        );
    }

    public function testExpression()
    {
        /*
         * PostgreSQL, at least versions before 10, needs to have the string cast to the
         * correct datatype.
         * But using CAST(.. AS CHAR) will return one single character on postgresql, but the
         * entire string on mysql.
         */
        if ($this->c->driverType === 'pgsql') {
            $this->assertSame(
                'foo',
                $this->e('select CAST([] AS TEXT)', ['foo'])->getOne()
            );
        } else {
            $this->assertSame(
                'foo',
                $this->e('select CAST([] AS CHAR)', ['foo'])->getOne()
            );
        }
    }

    /**
     * covers atk4\dsql\Expression::__toString, but on PHP 5.5 this hint doesn't work.
     */
    public function testCastingToString()
    {
        // simple value
        $this->assertSame(
            'Williams',
            (string) $this->q('employee')->field('surname')->where('name', 'Jack')
        );
        // table as sub-query
        $this->assertSame(
            'Williams',
            (string) $this->q($this->q('employee'), 'e2')->field('surname')->where('name', 'Jack')
        );
        // field as expression
        $this->assertSame(
            'Williams',
            (string) $this->q('employee')->field($this->e('surname'))->where('name', 'Jack')
        );
        // cast to string multiple times
        $q = $this->q('employee')->field('surname')->where('name', 'Jack');
        $this->assertSame(
            ['Williams', 'Williams'],
            [(string) $q, (string) $q]
        );
        // cast custom Expression to string
        $this->assertSame(
            '7',
            (string) $this->e('select 3+4')
        );
    }

    public function testOtherQueries()
    {
        // truncate table
        $this->q('employee')->truncate();
        $this->assertSame(
            '0',
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // insert
        $this->q('employee')
            ->set(['id' => 1, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
            ->insert();
        $this->q('employee')
            ->set(['id' => 2, 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
            ->insert();
        $this->assertSame(
            [['id' => '1', 'name' => 'John'], ['id' => '2', 'name' => 'Jane']],
            $this->q('employee')->field('id,name')->order('id')->get()
        );

        // update
        $this->q('employee')
            ->where('name', 'John')
            ->set('name', 'Johnny')
            ->update();
        $this->assertSame(
            [['id' => '1', 'name' => 'Johnny'], ['id' => '2', 'name' => 'Jane']],
            $this->q('employee')->field('id,name')->order('id')->get()
        );

        // replace
        if ($this->c->driverType === 'pgsql') {
            $this->q('employee')
                ->set(['name' => 'Peter', 'surname' => 'Doe', 'retired' => 1])
                ->where('id', 1)
                ->update();
        } else {
            $this->q('employee')
                ->set(['id' => 1, 'name' => 'Peter', 'surname' => 'Doe', 'retired' => 1])
                ->replace();
        }

        // In SQLite replace is just like insert, it just checks if there is
        // duplicate key and if it is it deletes the row, and inserts the new
        // one, otherwise it just inserts.
        // So order of records after REPLACE in SQLite will be [Jane, Peter]
        // not [Peter, Jane] as in MySQL, which in theory does the same thing,
        // but returns [Peter, Jane] - in original order.
        // That's why we add usort here.
        $data = $this->q('employee')->field('id,name')->get();
        usort($data, function ($a, $b) {
            return $a['id'] - $b['id'];
        });
        $this->assertSame(
            [['id' => '1', 'name' => 'Peter'], ['id' => '2', 'name' => 'Jane']],
            $data
        );

        // delete
        $this->q('employee')
            ->where('retired', 1)
            ->delete();
        $this->assertSame(
            [['id' => '2', 'name' => 'Jane']],
            $this->q('employee')->field('id,name')->get()
        );
    }

    public function testEmptyGetOne()
    {
        // truncate table
        $this->q('employee')->truncate();
        $this->expectException(Exception::class);
        $this->q('employee')->field('name')->getOne();
    }

    public function testExecuteException()
    {
        $this->expectException(\atk4\dsql\ExecuteException::class);

        try {
            $this->q('non_existing_table')->field('non_existing_field')->getOne();
        } catch (\atk4\dsql\ExecuteException $e) {
            // test error code
            $unknownFieldErrorCode = [
                'sqlite' => 1,    // SQLSTATE[HY000]: General error: 1 no such table: non_existing_table
                'mysql' => 1146, // SQLSTATE[42S02]: Base table or view not found: 1146 Table 'non_existing_table' doesn't exist
                'pgsql' => 7,    // SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "non_existing_table" does not exist
            ][$this->c->driverType];
            $this->assertSame($unknownFieldErrorCode, $e->getCode());

            // test debug query
            $expectedQuery = $this->c->driverType === 'mysql'
                ? 'select `non_existing_field` from `non_existing_table`'
                : 'select "non_existing_field" from "non_existing_table"';
            $this->assertSame(preg_replace('~\s+~', '', $expectedQuery), preg_replace('~\s+~', '', $e->getDebugQuery()));

            throw $e;
        }
    }
}
