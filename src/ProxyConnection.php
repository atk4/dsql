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

        if ($this->driver instanceof \atk4\dsql\Connection && $this->driver->driverType) {
            $this->driverType = $this->driver->driverType;
        }
    }

    public function driver()
    {
        return $this->driver->driver();
    }

    public function dsql($properties = []): Query
    {
        $dsql = $this->driver->dsql($properties);
        $dsql->connection = $this;

        return $dsql;
    }

    public function expr($properties = [], $arguments = null): Expression
    {
        $expr = $this->driver->expr($properties, $arguments);
        $expr->connection = $this;

        return $expr;
    }

    public function execute(Expression $expr)
    {
        return $this->driver->execute($expr);
    }
}
