<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Connection_Dumper extends Connection_Proxy
{
    /**
     * Callable to call for outputting.
     *
     * Will receive parameters:
     *  - Expression Expression object
     *  - float      How long it took to execute expression
     *  - boolean    True if we had exception while executing expression
     *
     * @var callable
     */
    public $callback = null;

    /**
     * @var float
     */
    protected $start_time;

    /**
     * Execute expression.
     *
     * @param Expression $expr
     *
     * @return \PDOStatement
     */
    public function execute(Expression $expr)
    {
        $this->start_time = microtime(true);

        try {
            $ret = parent::execute($expr);
            $took = microtime(true) - $this->start_time;

            if ($this->callback && is_callable($this->callback)) {
                call_user_func($this->callback, $expr, $took, false);
            } else {
                printf("[%02.6f] %s\n", $took, $expr->getDebugQuery());
            }
        } catch (\Exception $e) {
            $took = microtime(true) - $this->start_time;

            if ($this->callback && is_callable($this->callback)) {
                call_user_func($this->callback, $expr, $took, true);
            } else {
                printf("[ERROR %02.6f] %s\n", $took, $expr->getDebugQuery());
            }

            throw $e;
        }

        return $ret;
    }
}
