<?php

namespace atk4\dsql;

/**
 * Custom Connection class specifically for PostgreSQL database.
 */
class Connection_PgSQL extends Connection
{
    /** @var string Query classname */
    protected $query_class = Query_PgSQL::class;
}
