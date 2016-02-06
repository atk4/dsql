<?php

use atk4\dsql\Query;

class QueryTest extends PHPUnit_Framework_TestCase
{

    function q($args = []){
        return new Query($args);
    }

    function testTable()
    {
        $q = new Query();
        $res = $q->table('employee');
        $this->assertEquals(true, $res);
    }

    function testEscaping()
    {
        // escaping exclusions
        $this->assertEquals('`first_name`', $this->q()->_escape('first_name'));
        $this->assertEquals('*first_name*', $this->q(['escapeChar'=>'*'])->_escape('first_name'));
        $this->assertEquals('first_name.table', $this->q()->_escape('first_name.table'));
        $this->assertEquals('(2+2) age', $this->q()->_escape('(2+2) age'));
        $this->assertEquals('*', $this->q()->_escape('*'));

    }

    function testFieldBasic()
    {
        // excludes expressions
        $this->assertEquals('`first_name`', $this->q()->field('first_name')->_render_field());
        $this->assertEquals('`first_name`,`last_name`', $this->q()->field('first_name,last_name')->_render_field());
        $this->assertEquals('`emplayee`.`first_name`', $this->q()->field('first_name','emplayee')->_render_field());
        $this->assertEquals('`first_name` `name`', $this->q()->field('first_name',null,'name')->_render_field());
        $this->assertEquals('`first_name` `name`', $this->q()->field(['name'=>'first_name'])->_render_field());
        $this->assertEquals('`employee`.`first_name` `name`', $this->q()->field(['name'=>'first_name'],'employee')->_render_field());

        $this->assertEquals('*', $this->q()->_render_field());
        $this->assertEquals('id', $this->q(['defaultField'=>'id'])->_render_field());
        $this->assertEquals('*', $this->q()->field('*')->_render_field());
        $this->assertEquals('first_name.employee', $this->q()->field('first_name.employee')->_render_field());

    }

}
