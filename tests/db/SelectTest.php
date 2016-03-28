<?php
namespace atk4\dsql\tests;

use atk4\dsql\Query;
use atk4\dsql\Expression;

class dbSelectTest extends \PHPUnit_Extensions_Database_TestCase
{
    protected $pdo;
    
    public function __construct()
    {
        $this->pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        //$this->pdo->query('create database if not exists dsql_test');
        $this->pdo->query('create temporary table employee (id int not null, name text, surname text, retired bool, primary key (id))');
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
    
    private function q($table = null)
    {
        $q = new Query(['connection'=>$this->pdo]);

        if ($table !== null) {
            $q->table($table);
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

    public function testOtherQueries()
    {
        // truncate
        /**/echo strip_tags($this->q('employee')->selectTemplate('truncate')->getDebugQuery());

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
            ->set(['id' => 1, 'name' => 'Peter'])
            ->replace();
        $this->assertEquals(
            [['id'=>1, 'name'=>'Peter'], ['id'=>2, 'name'=>'Jane']],
            $this->q('employee')->field('id,name')->select()->fetchAll()
        );

        // delete
        /**/echo strip_tags($this->q('employee')->where('retired', 1)->selectTemplate('delete')->getDebugQuery());
        $this->q('employee')
            ->where('retired', 1)
            ->delete();
        $this->assertEquals(
            [['id'=>2, 'name'=>'Jane']],
            $this->q('employee')->field('id,name')->select()->fetchAll()
        );
    }
}
