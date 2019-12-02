<?php

namespace atk4\dsql\tests;

use atk4\dsql\Expression;

/**
 * @coversDefaultClass \atk4\dsql\Exception
 */
class ExceptionTest extends \atk4\core\PHPUnit_AgileTestCase
{
    /**
     * Test constructor.
     *
     * @covers ::__construct
     */
    public function testException1()
    {
        $this->setExpectedException('atk4\dsql\Exception');

        throw new \atk4\dsql\Exception();
    }

    public function testException2()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $e = new Expression('hello, [world]');
        $e->render();
    }

    public function testException3()
    {
        try {
            $e = new Expression('hello, [world]');
            $e->render();
        } catch (\atk4\dsql\Exception $e) {
            $this->assertEquals(
                'Expression could not render tag',
                $e->getMessage()
            );

            $this->assertEquals(
                'world',
                $e->getParams()['tag']
            );
        }
    }
}
