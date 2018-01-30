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
        $this->expr('ALTER SESSION SET NLS_DATE_FORMAT = {format}', ['format'=>'YYYY-MM-DD HH24:MI:SS'])->execute();
    }
}
