<?php

namespace atk4\dsql;

class ProxyConnection extends Connection
{
    /**
     * Specifying $properties to constructors will override default
     * property values of this class.
     *
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        parent::__construct($properties);

        if ($this->handler instanceof \atk4\dsql\Connection && $this->handler->driverType) {
            $this->driverType = $this->handler->driverType;
        }
    }

    public function handler()
    {
        return $this->handler->handler();
    }

    public function dsql($properties = []): Query
    {
        $dsql = $this->handler->dsql($properties);
        $dsql->connection = $this;

        return $dsql;
    }

    public function expr($properties = [], $arguments = null): Expression
    {
        $expr = $this->handler->expr($properties, $arguments);
        $expr->connection = $this;

        return $expr;
    }

    public function execute(Expression $expr)
    {
        return $this->handler->execute($expr);
    }
}
