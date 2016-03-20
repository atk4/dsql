<?php

namespace atk4\dsql\tests;

use atk4\dsql\Expression;

/**
 * @coversDefaultClass \atk4\dsql\Expression
 */
class ExpressionTest extends \PHPUnit_Framework_TestCase
{

    public function e()
    {
        $reflection = new \ReflectionClass("atk4\dsql\Expression");
        $instance = $reflection->newInstanceWithoutConstructor();
        return call_user_func_array(array($instance, '__construct'), func_get_args());
    }


    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        // Testing parameter edge cases - no parameters, null, empty strings and arrays etc.
        $this->assertEquals(
            '',
            (new Expression)->render()
        );
        $this->assertEquals(
            '',
            $this->e('')->render()
        );





        // Testing simple template patterns
        $this->assertEquals(
            'now()',
            $this->e('now()')->render()
        );

        // Testing template with parameter
        $e = $this->e('hello, [who]', ['who' => 'world']);
        $this->assertEquals('hello, :a', $e->render());
        $this->assertEquals('world', $e->params[':a']);

        // ...
        $this->assertEquals(
            'hello, world',
            $this->e('hello, [who]', ['who' => $this->e('world')])->render()
        );
        $this->assertEquals(
            'hello, world',
            $this->e(
                '[what], [who]',
                [
                    'what' => $this->e('hello'),
                    'who'  => $this->e('world')
                ]
            )->render()
        );
        $this->assertEquals(
            'testing "hello, world"',
            $this->e(
                'testing "[]"',
                [
                    $this->e(
                        '[what], [who]',
                        [
                            'what' => $this->e('hello'),
                            'who'  => $this->e('world')
                        ]
                    )
                ]
            )->render()
        );

        $this->assertEquals(
            'hello, world',
            $this->e(
                ['template' => 'hello, [who]'],
                ['who' => $this->e('world')]
            )->render()
        );

    }

    public function testNestedParams()
    {

        $q = new Expression("[] and []", [
            new Expression('++[]', [1]),
            new Expression('--[]', [2]),
        ]);

        $this->assertEquals(
            '++1 and --2 [:b, :a]',
            strip_tags($q->getDebugQuery())
        );

        $qq = new Expression("=== [foo] ===", ['foo'=>$q]);

        $this->assertEquals(
            '=== ++1 and --2 === [:b, :a]',
            strip_tags($qq->getDebugQuery())
        );

        $this->assertEquals(
            '++1 and --2 [:b, :a]',
            strip_tags($q->getDebugQuery())
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException1()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $e = new Expression(false);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException2()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $e = new Expression("hello, []", "hello");
    }

    /**
     * @covers ::_escape
     */
    public function testEscape()
    {
        // escaping expressions
        $this->assertEquals(
            '`first_name`',
            PHPUnitUtil::callProtectedMethod($this->e(''), '_escape', ['first_name'])
        );
        $this->assertEquals(
            '*first_name*',
            PHPUnitUtil::callProtectedMethod($this->e(['','escapeChar' => '*']), '_escape', ['first_name'])
        );

        // should not escape expressions
        $this->assertEquals(
            '*',
            PHPUnitUtil::callProtectedMethod($this->e(''), '_escape', ['*'])
        );
        $this->assertEquals(
            '(2+2) age',
            PHPUnitUtil::callProtectedMethod($this->e(''), '_escape', ['(2+2) age'])
        );
        $this->assertEquals(
            'first_name.table',
            PHPUnitUtil::callProtectedMethod($this->e(''), '_escape', ['first_name.table'])
        );
        $this->assertEquals(
            'first#name',
            PHPUnitUtil::callProtectedMethod($this->e(['','escapeChar'=>'#']), '_escape', ['first#name'])
        );
        //$this->assertEquals(
        //    true,
        //    is_object(PHPUnitUtil::callProtectedMethod($this->q(), '_escape', ["bleh"]))
        //);

        // escaping array - escapes each of its elements
        $this->assertEquals(
            ['`first_name`', '*', '`last_name`'],
            PHPUnitUtil::callProtectedMethod($this->e(''), '_escape', [ ['first_name', '*', 'last_name'] ])
        );
    }

    /**
     * Test for vendors that rely on JavaScript expressions, instead of parameters.
     */
    public function testJsonExpression()
    {
        $e = new JsonExpression('hello, [who]', ['who'=>'world']);
        
        $this->assertEquals(
            'hello, "world"',
            $e->render()
        );
        $this->assertEquals(
            [],
            $e->params
        );
    }
}


class JsonExpression extends Expression
{
    public function _param($value)
    {
        return json_encode($value);
    }
}
