<?php

namespace atk4\dsql\tests;

use atk4\dsql\Connection;
use atk4\dsql\Expression;
use atk4\dsql\Query;

/**
 * @coversDefaultClass \atk4\dsql\Exception
 */
class ExceptionTest extends \atk4\core\PHPUnit_AgileTestCase
{
    public function q()
    {
        return new Query(...func_get_args());
    }

    /**
     * Test constructor.
     *
     * @covers ::__construct
     */
    public function testException1()
    {
        $this->setExpectedException(\atk4\dsql\Exception::class);

        throw new \atk4\dsql\Exception();
    }

    public function testException2()
    {
        $this->setExpectedException(\atk4\dsql\Exception::class);
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

    public function testNonexistantFieldException()
    {
        $c = Connection::connect('sqlite::memory:');
        $q = $c->dsql();
        $q->table('dual')->field('do_not_exist');

        $this->setExpectedException(\atk4\dsql\Exception::class);
        $this->setExpectedExceptionMessage('should fail');
        $this->setExpectedExceptionCode(1);
        $q->execute(); // PDOException: SQLSTATE[HY000]: General error: 1 no such table: foo
    }
}
