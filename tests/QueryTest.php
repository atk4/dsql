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
        $this->assertEquals('`first_name`', $this->q()->escape('first_name'));
        $this->assertEquals('*first_name*', $this->q(['escapeChar'=>'*'])->escape('first_name'));
        $this->assertEquals('first_name.table', $this->q()->escape('first_name.table'));
        $this->assertEquals('(2+2) age', $this->q()->escape('(2+2) age'));

    }

    function testFieldBasic()
    {
        // excludes expressions
        $this->assertEquals('`first_name`', $this->q()->field('first_name')->render_field());
        $this->assertEquals('`first_name`,`last_name`', $this->q()->field('first_name,last_name')->render_field());
        $this->assertEquals('`emplayee`.`first_name`', $this->q()->field('first_name','emplayee')->render_field());
        $this->assertEquals('`first_name` `name`', $this->q()->field(['name'=>'first_name'])->render_field());
        $this->assertEquals('`employee`.`first_name` `name`', $this->q()->field(['name'=>'first_name'],'employee')->render_field());

    }

}
