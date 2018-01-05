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
    /**
     * Renders [limit].
     *
     * @return string rendered SQL chunk
     */
    public function _render_limit()
    {
        if (isset($this->args['limit'])) {
            $cnt = (int) $this->args['limit']['cnt'];
            $shift = (int) $this->args['limit']['shift'];

            return ' '.trim(
                ($shift ? 'OFFSET '.$shift.' ROWS' : '').
                ' '.
                // as per spec 'NEXT' is synonymous to 'FIRST', so not bothering with it.
                // https://docs.oracle.com/javadb/10.8.3.0/ref/rrefsqljoffsetfetch.html
                ($cnt ? 'FETCH NEXT '.$cnt.' ROWS ONLY' : '')
            );
        }
    }
}
