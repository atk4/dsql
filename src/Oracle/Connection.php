<?php

declare(strict_types=1);

namespace atk4\dsql\Oracle;

use atk4\dsql\Connection as BaseConnection;

/**
 * Custom Connection class specifically for Oracle database.
 */
class Connection extends BaseConnection
{
    public $driverType = 'oci';

    /** @var string Query classname */
    protected $query_class = Query::class;

    private static $_killerHack = [];

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

        // TODO hack to pass tests, currently, something prevents previous connection instances to be released
        // and thus high number of concurrent connections is active
        self::$_killerHack[] = PHP_MAJOR_VERSION > 7 || PHP_MINOR_VERSION === 4 ? \WeakReference::create($this) : $this;
        while (count(self::$_killerHack) > 50) {
            $c = array_shift(self::$_killerHack);
            if ($c instanceof \WeakReference) {
                $c = $c->get();
            }

            if ($c !== null) {
                $c->connection = null;
            }
        }
        gc_collect_cycles();
    }

    /**
     * Return last inserted ID value.
     *
     * Drivers like PostgreSQL need to receive sequence name to get ID because PDO doesn't support this method.
     */
    public function lastInsertId(string $sequence = null): string
    {
        if ($sequence) {
            return $this->dsql()->mode('seq_currval')->sequence($sequence)->getOne();
        }

        // fallback
        return parent::lastInsertId($sequence);
    }
}
