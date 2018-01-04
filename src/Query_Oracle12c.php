<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on Oracle server.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Query_Oracle12c extends Query_Oracle_Abstract
{
    // [limit] not supported. TODO - add rownum implementation instead
    protected $template_select = 'select[option] [field] [from] [table][join][where][group][having][order][limit]';

    public function _render_limit()
    {
        return
            ' '.
            ($this->args['limit']['shift'] ? 'OFFSET '.((int) $this->args['limit']['shift']).' ROWS' : '').
            ' '.
            ($this->args['limit']['cnt'] ? 'FETCH FIRST '.((int) $this->args['limit']['cnt']).' ROWS ONLY' : '');

        return $this->args['limit']['shift'];
    }
}
