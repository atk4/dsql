<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Custom Connection class specifically for Oracle 12c database.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Connection_Oracle12 extends Connection_Oracle
{
    /** @var string Query classname */
    protected $query_class = 'atk4\dsql\Query_Oracle12c';
}
