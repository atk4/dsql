<?php

use atk4\dsql\Query;

class QueryTest extends PHPUnit_Framework_TestCase
{

    function q(){
        return new Query();
    }

    function testTable()
    {
        $q = new Query();
        $res = $q->table('employee');
        $this->assertEquals(true, $res);
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
