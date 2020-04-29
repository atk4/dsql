<?php

namespace atk4\dsql;

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
     * @param \atk4\data\Model Optional data model from which to return last ID
     *
     * @return mixed
     */
    public function lastInsertID($m = null)
    {
        // PostGRE SQL PDO requires sequence name in lastInertID method as parameter
        try {
            return $this->connection()->lastInsertID($m->sequence ?: $m->table . '_' . $m->id_field . '_seq');
        } catch (\PDOException $e) {
            var_dump($this->connection()->errorInfo());
            throw $e;
            // if no sequence defined (we do not always need it), then silence PDO exception
            //return null;
        }
    }
}
