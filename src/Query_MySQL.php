<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on MySQL server.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Query_MySQL extends Query
{
    protected $template_update = 'update [table][join] set [set] [where]';
}
