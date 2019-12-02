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
    protected $query_class = 'atk4\dsql\Query_PgSQL';
}
