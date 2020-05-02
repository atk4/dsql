<?php

declare(strict_types=1);

namespace atk4\dsql\tests;

use atk4\core\AtkPhpunit;
use atk4\dsql\Expression;

/**
 * @coversDefaultClass \atk4\dsql\Exception
 */
class ExceptionTest extends AtkPhpunit\TestCase
{
    /**
     * Test constructor.
     *
     * @covers ::__construct
     */
    public function testException1()
    {
        $this->expectException(\atk4\dsql\Exception::class);

        throw new \atk4\dsql\Exception();
    }

    public function testException2()
    {
        $this->expectException(\atk4\dsql\Exception::class);
        $e = new Expression('hello, [world]');
        $e->render();
    }

    public function testException3()
    {
        try {
            $e = new Expression('hello, [world]');
            $e->render();
        } catch (\atk4\dsql\Exception $e) {
            $this->assertSame(
                'Expression could not render tag',
                $e->getMessage()
            );

            $this->assertSame(
                'world',
                $e->getParams()['tag']
            );
        }
    }
}
