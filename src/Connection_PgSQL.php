<?php

namespace atk4\dsql;

use atk4\data\Model;

/**
 * Custom Connection class specifically for PostgreSQL database.
 */
class Connection_PgSQL extends Connection
{
    /** @var string Query classname */
    protected $query_class = Query_PgSQL::class;

    /**
     * Return last inserted ID value.
     *
     * Few Connection drivers need to receive Model to get ID because PDO doesn't support this method.
     *
     * @param Model Optional data model from which to return last ID
     * @param string Optional sequence name from which to return last ID (this takes precedence)
     *
     * @return mixed
     */
    public function lastInsertID(Model $m = null, string $sequence = null)
    {
        // PostGRE SQL PDO requires sequence name in lastInertID method as parameter
        $seq = $sequence ?? ($m && $m->sequence ?: $m->table . '_' . $m->id_field . '_seq');

        return $this->connection()->lastInsertID($seq);
    }
}
