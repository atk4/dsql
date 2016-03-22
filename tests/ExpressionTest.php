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
        $args = func_get_args();
        switch (count($args)) {
            case 1:
                return new Expression($args[0]);
            case 2:
                return new Expression($args[0], $args[1]);
        }
        return new Expression();
    }



    /**
     * Test constructor exception - wrong 1st parameter.
     *
     * @covers ::__construct
     */
    public function testConstructorException_1st_1()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $this->e(null);
    }

    /**
     * Test constructor exception - wrong 1st parameter.
     *
     * @covers ::__construct
     */
    public function testConstructorException_1st_2()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $this->e(false);
    }

    /**
     * Test constructor exception - wrong 2nd parameter.
     *
     * @covers ::__construct
     */
    public function testConstructorException_2nd_1()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $this->e("hello, []", false);
    }

    /**
     * Test constructor exception - wrong 2nd parameter.
     *
     * @covers ::__construct
     */
    public function testConstructorException_2nd_2()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $this->e("hello, []", "hello");
    }

    /**
     * Testing parameter edge cases - empty strings and arrays etc.
     *
     * @covers ::__construct
     */
    public function testConstructor_1()
    {
        $this->assertEquals(
            '',
            $this->e('')->render()
        );
        $this->assertEquals(
            '',
            $this->e([])->render()
        );
    }

    /**
     * Testing simple template patterns without arguments.
     * Testing different ways how to pass template to constructor.
     *
     * @covers ::__construct
     * @covers ::_escape
     */
    public function testConstructor_2()
    {
        // pass as string
        $this->assertEquals(
            'now()',
            $this->e('now()')->render()
        );
        // pass as array without key
        $this->assertEquals(
            'now()',
            $this->e(['now()'])->render()
        );
        // pass as array with template key
        $this->assertEquals(
            'now()',
            $this->e(['template' => 'now()'])->render()
        );
        // pass as array without key and custom escapeChar
        $this->assertEquals(
            ':a Name',
            $this->e(['[] Name', 'escapeChar' => '*'], ['First'])->render()
        );
        // pass as array with template key and custom escapeChar
        $this->assertEquals(
            ':a Name',
            $this->e(['template' => '[] Name', 'escapeChar' => '*'], ['Last'])->render()
        );
    }

    /**
     * Testing template with simple arguments.
     *
     * @covers ::__construct
     */
    public function testConstructor_3()
    {
        $e = $this->e('hello, [who]', ['who' => 'world']);
        $this->assertEquals('hello, :a', $e->render());
        $this->assertEquals('world', $e->params[':a']);
    }

    /**
     * Testing template with complex arguments.
     *
     * @covers ::__construct
     */
    public function testConstructor_4()
    {
        // argument = Expression
        $this->assertEquals(
            'hello, world',
            $this->e('hello, [who]', ['who' => $this->e('world')])->render()
        );

        // multiple arguments = Expression
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

        // numeric argument = Expression
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

        // pass template as array
        $this->assertEquals(
            'hello, world',
            $this->e(
                ['template' => 'hello, [who]'],
                ['who' => $this->e('world')]
            )->render()
        );

    }

    /**
     * Test nested parameters
     *
     * @covers ::__construct
     * @covers ::_param
     * @covers ::getDebugQuery
     */
    public function testNestedParams()
    {
        // ++1 and --2
        $e1 = $this->e("[] and []", [
            $this->e('++[]', [1]),
            $this->e('--[]', [2]),
        ]);

        $this->assertEquals(
            '++1 and --2 [:b, :a]',
            strip_tags($e1->getDebugQuery())
        );

        $e2 = $this->e("=== [foo] ===", ['foo' => $e1]);

        $this->assertEquals(
            '=== ++1 and --2 === [:b, :a]',
            strip_tags($e2->getDebugQuery())
        );

        $this->assertEquals(
            '++1 and --2 [:b, :a]',
            strip_tags($e1->getDebugQuery())
        );
    }

    /**
     * Tests where one expression with parameter is used within several other expressions.
     *
     * @covers ::__construct
     * @covers ::render
     */
    public function testNestedExpressions()
    {
        $e1 = $this->e('Hello [who]', ['who' => 'world']);
        
        $e2 = $this->e('[greeting]! How are you.', ['greeting' => $e1]);
        $e3 = $this->e('It is me again. [greeting]', ['greeting' => $e1]);

        $s2 = $e2->render(); // Hello :a! How are you.
        $s3 = $e3->render(); // It is me again. Hello :a
        
        $e4 = $this->e('[] and good night', [$e1]);
        $s4 = $e4->render(); // Hello :a and good night

        $this->assertEquals('Hello :a! How are you.', $s2);
        $this->assertEquals('It is me again. Hello :a', $s3);
        $this->assertEquals('Hello :a and good night', $s4);
    }

    /**
     * expr() should return new Expression object and inherit connection from it.
     *
     * @covers ::expr
     */
    public function testExpr()
    {
        $e = $this->e(['connection' => new \stdClass()]);
        $this->assertEquals(true, $e->expr() instanceof \stdClass);
    }

    /**
     * Fully covers _escape method
     *
     * @covers ::_escape
     */
    public function testEscape()
    {
        // escaping expressions
        $this->assertEquals(
            '`first_name`',
            PHPUnitUtil::callProtectedMethod($this->e(), '_escape', ['first_name'])
        );
        $this->assertEquals(
            '*first_name*',
            PHPUnitUtil::callProtectedMethod($this->e(['escapeChar' => '*']), '_escape', ['first_name'])
        );
        $this->assertEquals(
            '`123`',
            PHPUnitUtil::callProtectedMethod($this->e(), '_escape', [123])
        );

        // should not escape expressions
        $this->assertEquals(
            '*',
            PHPUnitUtil::callProtectedMethod($this->e(), '_escape', ['*'])
        );
        $this->assertEquals(
            '(2+2) age',
            PHPUnitUtil::callProtectedMethod($this->e(), '_escape', ['(2+2) age'])
        );
        $this->assertEquals(
            'first_name.table',
            PHPUnitUtil::callProtectedMethod($this->e(), '_escape', ['first_name.table'])
        );
        $this->assertEquals(
            'first#name',
            PHPUnitUtil::callProtectedMethod($this->e(['escapeChar'=>'#']), '_escape', ['first#name'])
        );
        $this->assertEquals(
            true,
            PHPUnitUtil::callProtectedMethod($this->e(), '_escape', [new \stdClass()]) instanceof \stdClass
        );

        // escaping array - escapes each of its elements
        $this->assertEquals(
            ['`first_name`', '*', '`last_name`'],
            PHPUnitUtil::callProtectedMethod($this->e(), '_escape', [ ['first_name', '*', 'last_name'] ])
        );
    }

    /**
     * Test ArrayAccess implementation
     *
     * @covers ::offsetSet
     * @covers ::offsetExists
     * @covers ::offsetUnset
     * @covers ::offsetGet
     */
    public function testArrayAccess()
    {
        $e = $this->e('', ['parrot' => 'red', 'blue']);

        // offsetGet
        $this->assertEquals('red', $e['parrot']);
        $this->assertEquals('blue', $e[0]);

        // offsetSet
        $e['cat'] = 'black';
        $this->assertEquals('black', $e['cat']);
        $e['cat'] = 'white';
        $this->assertEquals('white', $e['cat']);
        
        // offsetExists, offsetUnset
        $this->assertEquals(true, isset($e['cat']));
        unset($e['cat']);
        $this->assertEquals(false, isset($e['cat']));

        // real-life example
        $age = $this->e('coalesce([age], [default_age])');
        $age['age'] = $this->e('year(now()) - year(birth_date)');
        $age['default_age'] = 18;
        $this->assertEquals('coalesce(year(now()) - year(birth_date), :a)', $age->render());
    }

    /**
     * Test IteratorAggregate implementation
     *
     * @covers ::getIterator
     */
    public function testIteratorAggregate()
    {
        // todo - can not test this without actual DB connection and executing expression
        null;
    }

    /**
     * Test for vendors that rely on JavaScript expressions, instead of parameters.
     *
     * @covers ::_param
     */
    public function testJsonExpression()
    {
        $e = new JsonExpression('hello, [who]', ['who' => 'world']);
        
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


// @codingStandardsIgnoreStart
class JsonExpression extends Expression
{
    public function _param($value)
    {
        return json_encode($value);
    }
}
// @codingStandardsIgnoreEnd
