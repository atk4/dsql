<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on SQLite server.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Query_SQLite extends Query
{
    /**
     * SQLite specific TRUNCATE template.
     *
     * @var string
     */
    protected $template_truncate = 'delete [from] [table_noalias]';
}
