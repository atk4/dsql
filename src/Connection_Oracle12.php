<?php

namespace atk4\dsql;

/**
 * Custom Connection class specifically for Oracle 12c database.
 */
class Connection_Oracle12 extends Connection_Oracle
{
    /** @var string Query classname */
    protected $query_class = Query_Oracle12c::class;
}
