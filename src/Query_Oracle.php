<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on Oracle server.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Query_Oracle extends Query
{

    // [limit] not supported. TODO - add rownum implementation instead
    protected $template_select = 'select[option] [field] [from] [table][join][where][group][having][order]';

    protected $template_select_limit = 'select [field] [from] (select[option] rownum nrpk, [field] [from] [table][join][where][group][having][order]) where nrpk>=[limit_start] and nrpk<[limit_end]';


    function limit() {
        $this->template_select = $this->template_select_limit;
    }

    function _render_limit_start()
    {
        return $this->args['limit']['shift'];
    }

    function _render_limit_end()
    {
        return $this->args['limit']['cnt'] + $this->args['limit']['shift'];
    }

    function _escape($value)
    {
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        return $value;
    }
}
