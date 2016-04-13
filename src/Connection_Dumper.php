<?php // vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * 
 */
class Connection_Dumper extends Connection
{
    protected $callback  = null;

    function connection()
    {
        return $this->connection->connection();
    }
    public function dsql($properties = [])
    {
        $dsql = $this->connection->dsql($properties);
        $dsql->connection = $this;
        return $dsql;
    }

    public function expr($properties = [], $arguments = null)
    {
        $expr = $this->connection->expr($properties, $arguments);
        $expr->connection = $this;
        return $expr;
    }

    public function execute(Expression $expr) {

        $this->start_time = time() + microtime();
        $ret = $this->connection->execute($expr);
        $took = time() + microtime() - $this->start_time;

        if ($this->callback) {
            $c = $this->callback;
            $c($expr, $took);
        } else {
            printf("[%02.6f] %s\n", $took, strip_tags($expr->getDebugQuery()));
        }

        return $ret;
    }
}
