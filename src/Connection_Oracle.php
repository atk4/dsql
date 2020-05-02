<?php

declare(strict_types=1);

namespace atk4\dsql;

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
     * Few Connection drivers need to receive sequence name to get ID because PDO doesn't support this method.
     *
     * @param string $sequence Optional sequence name from which to return last ID
     *
     * @return mixed
     */
    public function lastInsertID(string $sequence = null)
    {
        if ($sequence) {
            return $this->dsql()->mode('seq_currval')->sequence($sequence)->getOne();
        }

        // fallback
        return parent::lastInsertID($sequence);
    }
}
