<?php

use atk4\dsql\Query;

class QueryTest extends PHPUnit_Framework_TestCase
{
  function testTable()
  {
    $q = new Query();
    $res = $q->table('employee');
    $this->assertEquals(true, $res);
    $this->assertEquals(true, $res);
  }
}
