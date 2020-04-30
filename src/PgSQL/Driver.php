<?php

namespace atk4\dsql\PgSQL;

use atk4\dsql\Connection;

/**
 * Custom Connection class specifically for PostgreSQL database.
 */
class Driver extends Connection
{
    public $driverType = 'pgsql';

    protected $queryClass = Query::class;

    /**
     * Return last inserted ID value.
     *
     * Few Connection drivers need to receive Model to get ID because PDO doesn't support this method.
     *
     * @param \atk4\data\Model Optional data model from which to return last ID
     *
     * @return mixed
     */
    public function lastInsertID($model = null)
    {
        // PostGRE SQL PDO requires sequence name in lastInertID method as parameter
        try {
            return $this->driver->lastInsertID($model->sequence ?: $model->table . '_' . $model->id_field . '_seq');
        } catch (\PDOException $e) {
            // if no sequence defined (we do not always need it), then silence PDO exception
            return null;
        }
    }
}
