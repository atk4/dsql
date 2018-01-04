<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on Oracle server.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Query_Oracle12c extends Query
{
    // [limit] not supported. TODO - add rownum implementation instead
    protected $template_select = 'select[option] [field] [from] [table][join][where][group][having][order][limit]';

    public function _render_limit()
    {
        return 
            ($this->args['limit']['shift'] ? "OFFSET ".((int)$this->args['limit']['shift'])." ROWS":"").
            ($this->args['limit']['cnt'] ? "FETCH ".($this->args['limit']['shift']?'NEXT':'FIRST')." ROWS":"").
            " ONLY";


        return $this->args['limit']['shift'];
    }

    public function _escape($value)
    {
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        return '"'.$value.'"';
    }
}
