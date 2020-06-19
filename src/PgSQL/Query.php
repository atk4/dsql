<?php

declare(strict_types=1);

namespace atk4\dsql\PgSQL;

use atk4\dsql\Query as BaseQuery;

/**
 * Perform query operation on PostgreSQL server.
 */
class Query extends BaseQuery
{
    /**
     * Field, table and alias name escaping symbol.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $escape_char = '"';

    /**
     * UPDATE template.
     *
     * @var string
     */
    protected $template_update = 'update [table][join] set [set] [where]';

    /**
     * REPLACE template.
     *
     * @var string
     */
    protected $template_replace;

    /**
     * Renders [limit].
     *
     * @return string rendered SQL chunk
     */
    public function _render_limit()
    {
        if (isset($this->args['limit'])) {
            return ' limit ' .
                (int) $this->args['limit']['cnt'] .
                ' offset ' .
                (int) $this->args['limit']['shift'];
        }
    }

    /**
     * Returns a query for a function, which can be used as part of the GROUP
     * query which would concatenate all matching fields.
     *
     * MySQL, SQLite - group_concat
     * PostgreSQL - string_agg
     * Oracle - listagg
     *
     * @param mixed  $field
     * @param string $delimiter
     *
     * @return \atk4\dsql\Expression
     */
    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('string_agg({}, [])', [$field, $delimeter]);
    }
}
