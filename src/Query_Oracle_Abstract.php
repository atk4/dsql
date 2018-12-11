<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on Oracle server.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
abstract class Query_Oracle_Abstract extends Query
{
    /**
     * Field, table and alias name escaping symbol.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $escape_char = '"';

    /**
     * Templates to get current and next value from sequence.
     *
     * @var string
     */
    protected $template_seq_currval = 'select [sequence].CURRVAL from dual';
    protected $template_seq_nextval = '[sequence].NEXTVAL';

    /**
     * Set sequence.
     *
     * @param string $sequence
     *
     * @return $this
     */
    public function sequence($sequence)
    {
        $this->args['sequence'] = $sequence;

        return $this;
    }

    /**
     * Renders [sequence].
     *
     * @return string rendered SQL chunk
     */
    public function _render_sequence()
    {
        return $this->args['sequence'];
    }

    /**
     * Returns a query for a function, which can be used as part of the GROUP
     * query which would concatenate all matching fields.
     *
     * MySQL, SQLite - group_concat
     * PostgreSQL - string_agg
     * Oracle - listagg
     *
     * NOTE: LISTAGG() is only supported starting from Oracle 11g and up
     * https://stackoverflow.com/a/16771200/1466341
     *
     * @param mixed  $field
     * @param string $delimiter
     *
     * @return Expression
     */
    public function groupConcat($field, $delimeter = ',')
    {
        return $this->expr('listagg({}, [])', [$field, $delimeter]);
    }
}
