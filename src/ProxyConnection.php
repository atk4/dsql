<?php

declare(strict_types=1);

namespace atk4\dsql;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class ProxyConnection extends Connection
{
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

    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->connection->getDatabasePlatform();
    }
}
