<?php // vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * 
 */
class Connection_Counter extends Connection_Proxy
{
    protected $callback  = null;

    protected $select = 0;
    protected $query = 0;
    protected $expressions = 0;

    protected $rows = 0;

    public function iterate($ret){
        foreach($ret as $key => $row) {
            $this->rows++;
            yield $key => $row;
        }
    }

    public function execute(Expression $expr) {

        $ret = parent::execute($expr);


        return $this->iterate($ret);
    }
    function __destruct(){



        
        return;

        $took = time() + microtime() - $this->start_time;

        if ($this->callback) {
            $c = $this->callback;
            $c($expr, $took);
        } else {
            printf("[%02.6f] Queries: %i, Rows fetched: %i\n", $took, strip_tags($expr->getDebugQuery()));
        }

        return $ret;
    }
}
