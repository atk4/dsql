<?php
namespace atk4\dsql\tests;
use atk4\dsql\Query;
use atk4\dsql\Expression;



class dbSelectTest extends \PHPUnit_Extensions_Database_TestCase
{
    protected $pdo;
    function __construct()
    {
        $this->pdo = new \PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'] );
        $this->pdo->query('create temporary table employee (id int, name text, surname text, retired bool)');
    }
    protected function getConnection()
    {
        return $this->createDefaultDBConnection($this->pdo, $GLOBALS['DB_DBNAME']);
    }
    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/SelectTest.xml');
    }
    private function q($table = null){
        $q = new Query(['connection'=>$this->pdo]);

        if ($table !== null) {
            $q->table('employee');
        }
        return $q;
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
            $this->q('employee')->field('surname')->where('retired','1')->getRow()
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
        foreach($this->q('employee')->where('retired',false) as $row){
            $names[] = $row['name'];
        }
        $this->assertEquals(
            ['Oliver','Jack','Charlie'],
            $names
        );

        $this->assertEquals(
            [['now'=>4]],
            $this->q()->field(new Expression('2+2'),'now')->get()
        );

        $this->assertEquals(
            [['now'=>6]],
            $this->q()->field(new Expression('[]+[]',[3,3]),'now')->get()
        );
    }
}

