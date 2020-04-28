<?php

namespace atk4\dsql;

class Connection_Proxy extends Connection
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

        if ($this->connection instanceof \atk4\dsql\Connection && $this->connection->driverType) {
            $this->driverType = $this->connection->driverType;
        }
    }

    public function connection()
    {
        return $this->connection->connection();
    }

    public function dsql($properties = []): Query
    {
        $dsql = $this->connection->dsql($properties);
        $dsql->connection = $this;

        return $dsql;
    }

    public function expr($properties = [], $arguments = null): Expression
    {
        $expr = $this->connection->expr($properties, $arguments);
        $expr->connection = $this;

        return $expr;
    }

    public function execute(Expression $expr)
    {
        return $this->connection->execute($expr);
    }
}
