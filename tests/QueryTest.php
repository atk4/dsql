<?php

use atk4\dsql\Query;

class QueryTest extends PHPUnit_Framework_TestCase
{
  function testTable()
  {
    $q = new Query();
    $res = $q->table('employee');
    $this->assertEquals(true, $res);
  }

  function testField()
  {
    $q = new Query();
    $res = $q->field('name');
    $this->assertEquals(true, $res);
  }
}
