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
        //$class = $this->namespace . '\\' . $this->class;
        return new Query($args);
    }

    /**
     * @covers ::_escape
     */
    public function testEscape()
    {
        // escaping expressions

        $this->assertEquals('`first_name`',     PHPUnitUtil::callProtectedMethod(new Query(), '_escape', ['first_name']));
        $this->assertEquals('*first_name*',     PHPUnitUtil::callProtectedMethod(new Query(['escapeChar'=>'*']), '_escape', ['first_name']));

        // should not escape expressions
        $this->assertEquals('*',                PHPUnitUtil::callProtectedMethod(new Query(), '_escape', ['*']));
        $this->assertEquals('(2+2) age',        PHPUnitUtil::callProtectedMethod(new Query(), '_escape', ['(2+2) age']));
        $this->assertEquals('first_name.table', PHPUnitUtil::callProtectedMethod(new Query(), '_escape', ['first_name.table']));
        $this->assertEquals('first#name',       PHPUnitUtil::callProtectedMethod(new Query(['escapeChar'=>'#']), '_escape', ['first#name']));
        //$this->assertEquals(true,               is_object(PHPUnitUtil::callProtectedMethod(new Query(), '_escape', ["bleh"])));

        // escaping array - escapes each of its elements
        $this->assertEquals(
            ['`first_name`','`last_name`'],
            PHPUnitUtil::callProtectedMethod(new Query(), '_escape', [['first_name','last_name']])
        );
        $this->assertEquals(
            ['`first_name`','*','`last_name`'],
            PHPUnitUtil::callProtectedMethod(new Query(), '_escape', [ ['first_name', '*', 'last_name'] ])
        );
    }

    /**
     * @covers ::field
     */
    public function testFieldBasic()
    {
        $this->assertEquals('`first_name`',             PHPUnitUtil::callProtectedMethod($this->q()->field('first_name'), '_render_field'));
        $this->assertEquals('`first_name`,`last_name`', PHPUnitUtil::callProtectedMethod($this->q()->field('first_name,last_name'), '_render_field'));
        $this->assertEquals('`emplayee`.`first_name`',  PHPUnitUtil::callProtectedMethod($this->q()->field('first_name','emplayee'), '_render_field'));
        $this->assertEquals('`first_name` `name`',      PHPUnitUtil::callProtectedMethod($this->q()->field('first_name',null,'name'), '_render_field'));
        $this->assertEquals('`first_name` `name`',      PHPUnitUtil::callProtectedMethod($this->q()->field(['name'=>'first_name']), '_render_field'));
        $this->assertEquals(
            '`employee`.`first_name` `name`',
            PHPUnitUtil::callProtectedMethod($this->q()->field(['name'=>'first_name'],'employee'), '_render_field')
        );
        $this->assertEquals('*',                        PHPUnitUtil::callProtectedMethod($this->q(), '_render_field'));
        $this->assertEquals('id',                       PHPUnitUtil::callProtectedMethod($this->q(['defaultField'=>'id']), '_render_field'));
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
