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

    public $output_file = 'php://stderr';

    public function execute(Expression $expr)
    {
        $this->start_time = time() + microtime();
        $ret = parent::execute($expr);
        $took = time() + microtime() - $this->start_time;

        if ($this->callback) {
            $c = $this->callback;
            $c($expr, $took);
        } else {
            $stderr = fopen($this->output_file, 'w'); 
            $Message = sprintf("[%02.6f] %s\n", $took, $expr->getDebugQuery());
            fwrite($stderr,$Message); 
            fclose($stderr); 
        }

        return $ret;
    }
}
