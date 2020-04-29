<?php

namespace atk4\dsql\Counter;

use atk4\dsql\Expression;
use atk4\dsql\ProxyConnection;
use atk4\dsql\Query;

class Connection extends ProxyConnection
{
    const DEFAULT_DRIVER_TYPE = 'counter';

    /**
     * Callable to call for outputting.
     *
     * Will receive parameters:
     *  - int     Count of executed queries
     *  - int     Count of executed selects
     *  - int     Count of rows iterated
     *  - int     Count of executed expressions
     *  - boolean True if we had exception while executing expression
     *
     * @var callable
     */
    public $callback;

    /** @var int Count of executed selects */
    protected $selects = 0;

    /** @var int Count of executed queries */
    protected $queries = 0;

    /** @var int Count of executed expressions */
    protected $expressions = 0;

    /** @var int Count of rows iterated */
    protected $rows = 0;

    public static function createHandler(array $dsn)
    {
        return static::create($dsn['rest'], $dsn['user'], $dsn['pass']);
    }

    /**
     * Iterate (yield) array.
     *
     * @param array $ret
     *
     * @return mixed
     */
    public function iterate($ret)
    {
        foreach ($ret as $key => $row) {
            ++$this->rows;
            yield $key => $row;
        }
    }

    /**
     * Execute expression.
     *
     * @return mixed
     */
    public function execute(Expression $expr)
    {
        if ($expr instanceof Query) {
            ++$this->queries;
            if ($expr->mode === 'select' || $expr->mode === null) {
                ++$this->selects;
            }
        } else {
            ++$this->expressions;
        }

        try {
            $ret = parent::execute($expr);
        } catch (\Exception $e) {
            if ($this->callback && is_callable($this->callback)) {
                call_user_func($this->callback, $this->queries, $this->selects, $this->rows, $this->expressions, true);
            } else {
                printf(
                    "[ERROR] Queries: %3d, Selects: %3d, Rows fetched: %4d, Expressions %3d\n",
                    $this->queries,
                    $this->selects,
                    $this->rows,
                    $this->expressions
                );
            }

            throw $e;
        }

        return $this->iterate($ret);
    }

    /**
     * Log results when destructing.
     */
    public function __destruct()
    {
        if ($this->callback && is_callable($this->callback)) {
            call_user_func($this->callback, $this->queries, $this->selects, $this->rows, $this->expressions, false);
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
