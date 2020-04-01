<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Custom Connection class specifically for PostgreSQL database.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
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
     * @param \atk4\data\Model Optional data model from which to return last ID
     *
     * @return mixed
     */
    public function lastInsertID($m = null)
    {
        // PostGRE SQL PDO requires sequence name in lastInertID method as parameter
        return $this->connection()->lastInsertID($m->sequence ?: $m->table.'_'.$m->id_field.'_seq');
    }
}
