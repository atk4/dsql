<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Connection_Counter extends Connection_Proxy
{
    public $callback = null;

    protected $selects = 0;
    protected $queries = 0;
    protected $expressions = 0;

    protected $rows = 0;

    public function iterate($ret)
    {
        foreach ($ret as $key => $row) {
            $this->rows++;
            yield $key => $row;
        }
    }

    public function execute(Expression $expr)
    {
        if ($expr instanceof Query) {
            $this->queries++;
            if ($expr->mode === 'select' || $expr->mode === null) {
                $this->selects++;
            }
        } else {
            $this->expressions++;
        }

        $ret = parent::execute($expr);

        return $this->iterate($ret);
    }

    public function __destruct()
    {
        if ($this->callback) {
            $c = $this->callback;
            $c($this->queries, $this->selects, $this->rows, $this->expressions);
        } else {
            printf(
                "Queries: %3d, Selects: %3d, Rows fetched: %4d, Expressions %3d\n",
                $this->queries,
                $this->selects,
                $this->rows,
                $this->expressions
            );
        }
    }
}
