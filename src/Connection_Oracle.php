<?php

namespace atk4\dsql;

use atk4\data\Model;

/**
 * Custom Connection class specifically for Oracle database.
 */
class Connection_Oracle extends Connection
{
    /** @var string Query classname */
    protected $query_class = Query_Oracle::class;

    /**
     * Add some configuration for current connection session.
     *
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        parent::__construct($properties);

        // date and datetime format should be like this for Agile Data to correctly pick it up and typecast
        $this->expr('ALTER SESSION SET NLS_TIMESTAMP_FORMAT={datetime_format} NLS_DATE_FORMAT={date_format} NLS_NUMERIC_CHARACTERS={dec_char}', [
            'datetime_format' => 'YYYY-MM-DD HH24:MI:SS', // datetime format
            'date_format' => 'YYYY-MM-DD', // date format
            'dec_char' => '. ', // decimal separator, no thousands separator
        ])->execute();
    }

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
        $seq = $sequence ?? ($m && $m->sequence ? $m->sequence : null);

        if ($seq) {
            return $this->dsql()->mode('seq_currval')->sequence($seq)->getOne();
        } elseif ($m instanceof Model) {
            // otherwise we have to select max(id_field) - this can be bad for performance !!!
            // Imants: Disabled for now because otherwise this will work even if database use triggers or
            // any other mechanism to automatically increment ID and we can't tell this line to not execute.
            //return $this->expr('SELECT max([field]) FROM [table]', ['field'=>$m->id_field, 'table'=>$m->table])->getOne();
        }

        // fallback
        return parent::lastInsertID($m, $sequence);
    }
}
