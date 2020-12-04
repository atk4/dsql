<?php

declare(strict_types=1);

namespace Atk4\Dsql\Debug\Stopwatch;

use atk4\dsql\Expression;
use atk4\dsql\ProxyConnection;

class Connection extends ProxyConnection
{
    public $driverType = 'stopwatch';

    /**
     * Closure to call for outputting.
     *
     * Will receive parameters:
     *  - Expression Expression object
     *  - float      How long it took to execute expression
     *  - boolean    True if we had exception while executing expression
     *
     * @var \Closure
     */
    public $callback;

    /**
     * @var float
     */
    protected $startTime;

    protected static function connectDriver(array $dsn)
    {
        return static::connect($dsn['rest'], $dsn['user'], $dsn['pass']);
    }

    /**
     * Execute expression.
     *
     * @return \PDOStatement
     */
    public function execute(Expression $expr)
    {
        $this->startTime = microtime(true);

        try {
            $ret = parent::execute($expr);

            $this->dump($expr);
        } catch (\Exception $e) {
            $this->dump($expr, true);

            throw $e;
        }

        return $ret;
    }

    protected function dump(Expression $expr, $error = false)
    {
        $took = microtime(true) - $this->startTime;

        if ($this->callback instanceof \Closure) {
            ($this->callback)($expr, $took, false);
        } else {
            printf('[%s%02.6f] %s' . "\n", $error ? 'ERROR ' : '', $took, $expr->getDebugQuery());
        }
    }
}
