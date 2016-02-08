<?php
namespace atk4\dsql\tests;

/**
 * Abstract class which adds additional functionality for PHPUnit TestCase
 * All project test classes should extend this class
 *
 * @todo Move this class to separate file
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Name of class which we're testing with all these tests
     */
    public $class;
    private $namespace = 'atk4\\dsql';
    
    /**
     * Create new object of $this->class class and return it
     * You can pass arguments to this method too
     *
     * @return Object
     */
    public function obj()
    {
        $class = $this->namespace . '\\' . $this->class;
        $args = func_get_args();
        return (new \ReflectionClass($class))->newInstanceArgs($args);
    }
    
    /**
     * Call protected/private method of a class
     *
     * Examples:
     *  $this->invokeMethod('_escape', ['first_name'])
     *  $this->invokeMethod($this->obj(['escapeChar'=>'*']), '_escape', ['first_name'])
     *
     * @param object $object     Optional instantiated object that we will run method on
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method
     *
     * @return mixed Method return.
     */
    public function invokeMethod($object, $methodName, array $parameters = array())
    {
        // $object parameter is optional
        if (!is_object($object)) {
            $parameters = $methodName;
            $methodName = $object;
            $object = $this->obj();
        }
        
        // create reflection
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}





/**
 * @coversDefaultClass \atk4\dsql\Query
 */
class QueryTest extends AbstractTestCase
{
    /**
     * Name of class which we're testing with all these tests
     */
    public $class = 'Query';
    
    /**
     * @covers ::_escape
     */
    public function testEscaping()
    {
        // escaping expressions
        $this->assertEquals('`first_name`',     $this->invokeMethod('_escape', ['first_name']));
        $this->assertEquals('*first_name*',     $this->invokeMethod($this->obj(['escapeChar'=>'*']), '_escape', ['first_name']));
        
        // should not escape expressions
        $this->assertEquals('*',                $this->invokeMethod('_escape', ['*']));
        $this->assertEquals('(2+2) age',        $this->invokeMethod('_escape', ['(2+2) age']));
        $this->assertEquals('first_name.table', $this->invokeMethod('_escape', ['first_name.table']));
        $this->assertEquals('first#name',       $this->invokeMethod($this->obj(['escapeChar'=>'#']), '_escape', ['first#name']));
        $this->assertEquals(true,               is_object($this->invokeMethod('_escape', [$this->obj()])));
        
        // escaping array - escapes each of its elements
        $this->assertEquals(
                json_encode(['`first_name`','*','`last_name`']),
                json_encode($this->invokeMethod('_escape', [ ['first_name', '*', 'last_name'] ]))
            );
    }

    /**
     * @covers ::field
     */
    /*
    public function testFieldBasic()
    {
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
    */

    /**
     * @covers ::table
     */
    /*
    public function testTable()
    {
        $this->assertEquals(true, $this->invokeMethod('table', ['employee']));
    }
    */
}
