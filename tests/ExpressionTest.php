<?php

namespace atk4\dsql\tests;

use atk4\dsql\Expression;
use atk4\dsql\Expressionable;
use atk4\dsql\Query;

/**
 * @coversDefaultClass \atk4\dsql\Expression
 */
class ExpressionTest extends \atk4\core\PHPUnit_AgileTestCase
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
     * @expectedException atk4\dsql\Exception
     */
    public function testConstructorException_1st_1()
    {
        $this->e(null);
    }

    /**
     * Test constructor exception - wrong 1st parameter.
     *
     * @covers ::__construct
     * @expectedException atk4\dsql\Exception
     */
    public function testConstructorException_1st_2()
    {
        $this->e(false);
    }

    /**
     * Test constructor exception - wrong 2nd parameter.
     *
     * @expectedException atk4\dsql\Exception
     */
    public function testConstructorException_2nd_1()
    {
        $this->e('hello, []', false);
    }

    /**
     * Test constructor exception - wrong 2nd parameter.
     *
     * @expectedException atk4\dsql\Exception
     */
    public function testConstructorException_2nd_2()
    {
        $this->e('hello, []', 'hello');
    }

    /**
     * Test constructor exception - no arguments.
     *
     * @expectedException atk4\dsql\Exception
     */
    public function testConstructorException_0arg()
    {
        // Template is not defined for Expression
        $this->e()->render();
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
        // pass as array without key
        $this->assertEquals(
            ':a Name',
            $this->e(['[] Name'], ['First'])->render()
        );
        // pass as array with template key
        $this->assertEquals(
            ':a Name',
            $this->e(['template' => '[] Name'], ['Last'])->render()
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

        $e = $this->e('hello, {who}', ['who' => 'world']);
        $this->assertEquals('hello, "world"', $e->render());
        $this->assertEquals([], $e->params);
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
                    'who'  => $this->e('world'),
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
                            'who'  => $this->e('world'),
                        ]
                    ),
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
     * Test nested parameters.
     *
     * @covers ::__construct
     * @covers ::_param
     * @covers ::getDebugQuery
     */
    public function testNestedParams()
    {
        // ++1 and --2
        $e1 = $this->e('[] and []', [
            $this->e('++[]', [1]),
            $this->e('--[]', [2]),
        ]);

        $this->assertEquals(
            '++1 and --2',
            strip_tags($e1->getDebugQuery())
        );

        $e2 = $this->e('=== [foo] ===', ['foo' => $e1]);

        $this->assertEquals(
            '=== ++1 and --2 ===',
            strip_tags($e2->getDebugQuery())
        );

        $this->assertEquals(
            '++1 and --2',
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
     * @covers ::__toString
     * @expectedException \Exception
     */
    /*
    public function testToStringException1()
    {
        $e = new MyBadExpression('Hello');
        $s = (string)$e;
    }
    */

    /**
     * expr() should return new Expression object and inherit connection from it.
     *
     * @covers ::expr
     */
    public function testExpr()
    {
        $e = $this->e(['connection' => new \stdClass()]);
        $this->assertEquals(true, $e->expr()->connection instanceof \stdClass);
    }

    /**
     * Fully covers _escape method.
     *
     * @covers ::_escape
     * @covers ::_escapeSoft
     * @covers ::escape
     */
    public function testEscape()
    {
        // escaping expressions
        $this->assertEquals(
            '"first_name"',
            $this->callProtected($this->e(), '_escape', ['first_name'])
        );
        $this->assertEquals(
            '"123"',
            $this->callProtected($this->e(), '_escape', [123])
        );
        $this->assertEquals(
            '"he""llo"',
            $this->callProtected($this->e(), '_escape', ['he"llo'])
        );

        // should not escape expressions
        $this->assertEquals(
            '*',
            $this->callProtected($this->e(), '_escapeSoft', ['*'])
        );
        $this->assertEquals(
            '"*"',
            $this->callProtected($this->e(), '_escape', ['*'])
        );
        $this->assertEquals(
            '(2+2) age',
            $this->callProtected($this->e(), '_escapeSoft', ['(2+2) age'])
        );
        $this->assertEquals(
            '"(2+2) age"',
            $this->callProtected($this->e(), '_escape', ['(2+2) age'])
        );
        $this->assertEquals(
            '"users"."first_name"',
            $this->callProtected($this->e(), '_escapeSoft', ['users.first_name'])
        );
        $this->assertEquals(
            '"users".*',
            $this->callProtected($this->e(), '_escapeSoft', ['users.*'])
        );
        $this->assertEquals(
            true,
            $this->callProtected($this->e(), '_escapeSoft', [new \stdClass()]) instanceof \stdClass
        );

        // escaping array - escapes each of its elements using hard escape
        $this->assertEquals(
            ['"first_name"', '*', '"last_name"'],
            $this->callProtected($this->e(), '_escapeSoft', [['first_name', '*', 'last_name']])
        );

        // escaping array - escapes each of its elements using hard escape
        $this->assertEquals(
            ['"first_name"', '"*"', '"last_name"'],
            $this->callProtected($this->e(), '_escape', [['first_name', '*', 'last_name']])
        );

        $this->assertEquals(
            '"first_name"',
            $this->e()->escape('first_name')->render()
        );
        $this->assertEquals(
            '"first""_name"',
            $this->e()->escape('first"_name')->render()
        );
        $this->assertEquals(
            '"first""_name {}"',
            $this->e()->escape('first"_name {}')->render()
        );
    }

    /**
     * Fully covers _param method.
     *
     * @covers ::_param
     */
    public function testParam()
    {
        $e = new Expression('hello, [who]', ['who' => 'world']);
        $this->assertEquals(
            'hello, :a',
            $e->render()
        );
        $this->assertEquals(
            [':a' => 'world'],
            $e->params
        );

        // @todo Imants: allowing to pass value as array looks wrong.
        //      See test case in testParam() method.
        //      Maybe we should add implode(' ', array_map(...)) here ?
        $e = new Expression('hello, [who]', ['who' => ['cruel', 'world']]);
        $this->assertEquals(
            'hello, (:a,:b)',
            $e->render()
        );
        $this->assertEquals(
            [':a' => 'cruel', ':b' => 'world'],
            $e->params
        );
    }

    /**
     * @covers ::_consume
     */
    public function testConsume()
    {
        // few brief tests on _consume
        $this->assertEquals(
            '"123"',
            $this->callProtected($this->e(), '_consume', [123, 'escape'])
        );
        $this->assertEquals(
            ':x',
            $this->callProtected($this->e(['_paramBase' => 'x']), '_consume', [123, 'param'])
        );
        $this->assertEquals(
            123,
            $this->callProtected($this->e(), '_consume', [123, 'none'])
        );
        $this->assertEquals(
            '(select *)',
            $this->callProtected($this->e(), '_consume', [new Query()])
        );

        $this->assertEquals(
            'hello, "myfield"',
            $this->e('hello, []', [new MyField()])->render()
        );
    }

    /**
     * $escape_mode value is incorrect.
     *
     * @covers ::_consume
     * @expectedException atk4\dsql\Exception
     */
    public function testConsumeException1()
    {
        $this->callProtected($this->e(), '_consume', [123, 'blahblah']);
    }

    /**
     * Only Expressions or Expressionable objects may be used in Expression.
     *
     * @covers ::_consume
     * @expectedException atk4\dsql\Exception
     */
    public function testConsumeException2()
    {
        $this->callProtected($this->e(), '_consume', [new \StdClass()]);
    }

    /**
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

        // testing absence of specific key in asignment
        $e = $this->e('[], []');
        $e[] = 'Hello';
        $e[] = 'World';
        $this->assertEquals("'Hello', 'World'", strip_tags($e->getDebugQuery()));

        // real-life example
        $age = $this->e('coalesce([age], [default_age])');
        $age['age'] = $this->e('year(now()) - year(birth_date)');
        $age['default_age'] = 18;
        $this->assertEquals('coalesce(year(now()) - year(birth_date), :a)', $age->render());
    }

    /**
     * Test IteratorAggregate implementation.
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
     * @coversNothing
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

    /**
     * Test reset exception if tag is not a string.
     *
     * @expectedException atk4\dsql\Exception
     */
    public function testResetException()
    {
        $this->e('test')->reset($this->e());
    }

    /**
     * Test var-dump code for codecoverage.
     *
     * @covers ::__debugInfo
     */
    public function testVarDump()
    {
        $this->e('test')->__debugInfo();

        $this->e(' [nosuchtag] ')->__debugInfo();
    }

    /**
     * Test reset().
     *
     * @covers ::reset
     */
    public function testReset()
    {
        // reset everything
        $e = $this->e('hello, [name] [surname]', ['name' => 'John', 'surname' => 'Doe']);
        $e->reset();
        $this->assertAttributeEquals(
            ['custom' => []],
            'args',
            $e
        );

        // reset particular custom/tag
        $e = $this->e('hello, [name] [surname]', ['name' => 'John', 'surname' => 'Doe']);
        $e->reset('surname');
        $this->assertAttributeEquals(
            ['custom' => ['name' => 'John']],
            'args',
            $e
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
class MyField implements Expressionable
{
    public function getDSQLExpression($e)
    {
        return $e->expr('"myfield"');
    }
}
/*
class MyBadExpression extends Expression
{
    public function getOne()
    {
        // should return string, but for test case we return array to get \Exception
        return array();
    }
}
*/
// @codingStandardsIgnoreEnd
