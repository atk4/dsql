<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Custom Connection class specifically for Oracle database.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Connection_Oracle extends Connection
{
    /** @var string Query classname */
    protected $query_class = 'atk4\dsql\Query_Oracle';

    /**
     * Add some configuration for current connection session.
     *
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        parent::__construct($properties);

        // date and datetime format should be like this for Agile Data to correctly pick it up and typecast
        $this->expr('ALTER SESSION SET NLS_DATE_FORMAT={format} NLS_NUMERIC_CHARACTERS={dec_char}', [
                'format'   => 'YYYY-MM-DD HH24:MI:SS', // datetime format
                'dec_char' => '. ', // decimal separator, no thousands separator
            ])->execute();
    }

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
        if ($m instanceof \atk4\data\Model) {
            // if we use sequence, then we can easily get current value
            if (isset($m->sequence)) {
                return $this->dsql()->mode('seq_currval')->sequence($m->sequence)->getOne();
            }

            // otherwise we have to select max(id_field) - this can be bad for performance !!!
            // Imants: Disabled for now because otherwise this will work even if database use triggers or any other mechanism
            // to automatically increment ID and we can't tell this line to not execute.
            //return $this->expr('SELECT max([field]) FROM [table]', ['field'=>$m->id_field, 'table'=>$m->table])->getOne();
        }

        // fallback
        return parent::lastInsertID($m);
    }
}
