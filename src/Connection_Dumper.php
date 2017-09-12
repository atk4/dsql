<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Connection_Dumper extends Connection_Proxy
{
    public $callback = null;

    public function execute(Expression $expr)
    {
        $this->start_time = microtime(true);

        try {
            $ret = parent::execute($expr);
            $took = microtime(true) - $this->start_time;
            if ($this->callback) {
                $c = $this->callback;
                $c($expr, $took);
            } else {
                printf("[%02.6f] %s\n", $took, $expr->getDebugQuery());
            }
        } catch (\Exception $e) {
            $took = microtime(true) - $this->start_time;
            if ($this->callback) {
                $c = $this->callback;
                $c($expr, $took, true);
            } else {
                printf("[ERROR %02.6f] %s\n", $took, $expr->getDebugQuery());
            }

            throw $e;
        }

        return $ret;
    }
}
