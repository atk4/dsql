<?php

namespace atk4\dsql\MySQL;

use atk4\dsql\Query as BaseQuery;

/**
 * Perform query operation on MySQL server.
 */
class Query extends BaseQuery
{
    /**
     * Field, table and alias name escaping symbol.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $quotedIdentifier = '`';

    /** @var string Expression classname */
    protected $expressionClass = Expression::class;

    /**
     * UPDATE template.
     *
     * @var string
     */
    protected $template_update = 'update [table][join] set [set] [where]';

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
        return $this->expr('group_concat({} separator [])', [$field, $delimeter]);
    }
}
