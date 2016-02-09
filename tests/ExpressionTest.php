<?php

use atk4\dsql\Expression;

class ExpressionTest extends PHPUnit_Framework_TestCase
{

    function e($template = null, $args = null){
        return new Expression($template, $args);
    }


    function testConstructor()
    {
        $this->assertEquals('', $this->e('')->render());
        $this->assertEquals('now()', $this->e('now()')->render());

        $e = $this->e('hello, [who]', ['who'=>'world']);
        $this->assertEquals('hello, :a', $e->render());
        $this->assertEquals('world', $e->param['a']);

        $this->assertEquals('hello, world', $this->e('hello, [who]', ['who'=>$this->e('world')])->render());
        $this->assertEquals('hello, world', $this->e('[what], [who]', ['what'=>$this->e('hello'), 'who'=>$this->e('world')])->render());
        $this->assertEquals('testing "hello, world"', $this->e(
            'testing "[]"',[
                $this->e(
                    '[what], [who]', [
                        'what'=>$this->e('hello'),
                        'who'=>$this->e('world')
                    ]
                )
            ]
        )->render());
    }

    /**
     * Test for vendors that rely on JavaScript expressions, instead of parameters.
     */

    function testJsonExpression()
    {
        $e = new JsonExpression('hello, [who]', ['who'=>'world']);
        $this->assertEquals('hello, "world"', $e->render());
        $this->assertEquals([], $e->params);
    }
}


class JsonExpression extends Expression {
    function _param($value){
        return json_encode($value);
    }
}
