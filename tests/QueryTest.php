<?php
namespace atk4\dsql\tests;
use atk4\dsql\Query;



/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Name of class which we're testing with all these tests
     */
    public function q($args = [])
    {
        return new Query($args);
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        // passing arguments in constructor
        $this->assertEquals('#', $this->q(['escapeChar' => '#'])->escapeChar);
    }


    /**
     * @covers ::field
     */
    public function testFieldBasic()
    {
        $this->assertEquals('`first_name`',             PHPUnitUtil::callProtectedMethod($this->q()->field('first_name'), '_render_field'));
        $this->assertEquals('`first_name`,`last_name`', PHPUnitUtil::callProtectedMethod($this->q()->field('first_name,last_name'), '_render_field'));
        $this->assertEquals('`employee`.`first_name`',  PHPUnitUtil::callProtectedMethod($this->q()->field('first_name', 'employee'), '_render_field'));
        $this->assertEquals('`first_name` `name`',      PHPUnitUtil::callProtectedMethod($this->q()->field('first_name', null, 'name'), '_render_field'));
        $this->assertEquals('`first_name` `name`',      PHPUnitUtil::callProtectedMethod($this->q()->field(['name' => 'first_name']), '_render_field'));
        $this->assertEquals(
            '`employee`.`first_name` `name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field(['name'=>'first_name'],'employee'), '_render_field')
        );
        $this->assertEquals('*',                        PHPUnitUtil::callProtectedMethod($this->q(), '_render_field'));
        $this->assertEquals('id',                       PHPUnitUtil::callProtectedMethod($this->q(['defaultField' => 'id']), '_render_field'));
        $this->assertEquals('*',                        PHPUnitUtil::callProtectedMethod($this->q()->field('*'), '_render_field'));
        $this->assertEquals('first_name.employee',      PHPUnitUtil::callProtectedMethod($this->q()->field('first_name.employee'), '_render_field'));
    }

    /**
     * @covers ::table
     */
    public function testTable()
    {
        $this->assertEquals(true, $this->q()->table('employee'));
    }
}
