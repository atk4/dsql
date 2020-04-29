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
     * @param Model|string Optional data model from which to return last ID or sequence name
     *
     * @return mixed
     */
    public function lastInsertID($m = null)
    {
        // PostGRE SQL PDO requires sequence name in lastInertID method as parameter
        try {
            $seq = is_string($m) ? $m : ($m->sequence ?: $m->table . '_' . $m->id_field . '_seq');
            return $this->connection()->lastInsertID($seq);
        } catch (\PDOException $e) {
            throw $e;
            // if no sequence defined (we do not always need it), then silence PDO exception
            //var_dump($this->connection()->errorInfo()); // 42P01: ERROR:  relation "__seq" does not exist
            //return null;
        }
    }
}
