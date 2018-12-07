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
    /**
     * Field, table and alias name escaping symbol.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $escape_char = '`';

    /** @var string Expression classname */
    protected $expression_class = 'atk4\dsql\Expression_MySQL';

    /**
     * UPDATE template.
     *
     * @var string
     */
    protected $template_update = 'update [table][join] set [set] [where]';

    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('group_concat({} separator [])', [$field, $delimeter]);
    }
}
