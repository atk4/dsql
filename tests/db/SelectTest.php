<?php
namespace atk4\dsql\tests;

use atk4\dsql\Expression;
use atk4\dsql\Query;
use atk4\dsql\Query\MySQL as Query_MySQL;
use atk4\dsql\Query\SQLite as Query_SQLite;

class dbSelectTest extends \PHPUnit_Extensions_Database_TestCase
{
    protected $pdo;

    public function __construct()
    {
        $this->pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
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
        // decide which DB engine to use
        $engine = strtolower(explode(':', $GLOBALS['DB_DSN'])[0]);
        switch ($engine) {
            case 'sqlite':
                $q = new Query_SQLite(['connection'=>$this->pdo]);
                break;
            case 'mysql':
                $q = new Query_MySQL(['connection'=>$this->pdo]);
                break;
            default:
                $q = new Query(['connection'=>$this->pdo]);
        }

        // add table to query if specified
        if ($table !== null) {
            $q->table($table, $alias);
        }
        return $q;
    }
    private function e($template = null, $args = null)
    {
        return $this->q()->expr($template, $args);
    }



    public function testBasicQueries()
    {
        $this->assertEquals(4, $this->getConnection()->getRowCount('employee'));

        $this->assertEquals(
            ['name'=>'Oliver','surname'=>'Smith'],
            $this->q('employee')->field('name,surname')->getRow()
        );

        $this->assertEquals(
            ['surname'=>'Taylor'],
            $this->q('employee')->field('surname')->where('retired', '1')->getRow()
        );

        $this->assertEquals(
            4,
            $this->q()->field(new Expression('2+2'))->getOne()
        );

        $this->assertEquals(
            4,
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        $names = [];
        foreach ($this->q('employee')->where('retired', false) as $row) {
            $names[] = $row['name'];
        }
        $this->assertEquals(
            ['Oliver','Jack','Charlie'],
            $names
        );

        $this->assertEquals(
            [['now'=>4]],
            $this->q()->field(new Expression('2+2'), 'now')->get()
        );

        $this->assertEquals(
            [['now'=>6]],
            $this->q()->field(new Expression('[]+[]', [3,3]), 'now')->get()
        );
    }

    public function testExpression()
    {
        $this->assertEquals(
            'foo',
            $this->e('select []', ['foo'])->getOne()
        );
    }

    public function testCastingToString()
    {
        // simple value
        $this->assertEquals(
            'Williams',
            (string)$this->q('employee')->field('surname')->where('name', 'Jack')
        );
        // table as sub-query
        $this->assertEquals(
            'Williams',
            (string)$this->q($this->q('employee'), 'e2')->field('surname')->where('name', 'Jack')
        );
        // field as expression
        $this->assertEquals(
            'Williams',
            (string)$this->q('employee')->field($this->e('surname'))->where('name', 'Jack')
        );
        // cast to string multiple times
        $q = $this->q('employee')->field('surname')->where('name', 'Jack');
        $this->assertEquals(
            ['Williams', 'Williams'],
            [ (string)$q, (string)$q ]
        );
        // cast custom Expression to string
        $this->assertEquals(
            '7',
            (string)$this->e('select 3+4')
        );
    }

    public function testOtherQueries()
    {
        // truncate table
        $this->q('employee')->truncate();
        $this->assertEquals(
            0,
            $this->q('employee')->field(new Expression('count(*)'))->getOne()
        );

        // insert
        $this->q('employee')
            ->set(['id' => 1, 'name' => 'John', 'surname' => 'Doe', 'retired' => 1])
            ->insert();
        $this->q('employee')
            ->set(['id' => 2, 'name' => 'Jane', 'surname' => 'Doe', 'retired' => 0])
            ->insert();
        $this->assertEquals(
            [['id'=>1, 'name'=>'John'], ['id'=>2, 'name'=>'Jane']],
            $this->q('employee')->field('id,name')->select()->fetchAll()
        );

        // update
        $this->q('employee')
            ->where('name', 'John')
            ->set('name', 'Johnny')
            ->update();
        $this->assertEquals(
            [['id'=>1, 'name'=>'Johnny'], ['id'=>2, 'name'=>'Jane']],
            $this->q('employee')->field('id,name')->select()->fetchAll()
        );

        // replace
        $this->q('employee')
            ->set(['id' => 1, 'name' => 'Peter', 'surname' => 'Doe', 'retired' => 1])
            ->replace();

        // In SQLite replace is just like insert, it just checks if there is duplicate key and if it is
        // it deletes the row, and inserts the new one, otherwise it just inserts.
        // So order of records after REPLACE in SQLite will be [Jane, Peter] not [Peter, Jane] as in MySQL,
        // which in theory does the same thing, but returns [Peter, Jane] - in original order.
        // That's why we add usort here.
        $data = $this->q('employee')->field('id,name')->select()->fetchAll();
        usort($data, function ($a, $b) {
            return $a['id'] - $b['id'];
        });
        $this->assertEquals(
            [['id'=>1, 'name'=>'Peter'], ['id'=>2, 'name'=>'Jane']],
            $data
        );

        // delete
        $this->q('employee')
            ->where('retired', 1)
            ->delete();
        $this->assertEquals(
            [['id'=>2, 'name'=>'Jane']],
            $this->q('employee')->field('id,name')->select()->fetchAll()
        );
    }
}
