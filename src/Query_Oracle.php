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

    protected $template_select_limit = 'select [field] [from] (select[option] __dsql_rownum nrpk, [field] [from] [table][join][where][group][having][order]) where __dsql_rownum>=[limit_start][and_limit_end]';

    public function limit($cnt, $shift = NULL)
    {
        // This is for pre- 12c version
        $this->template_select = $this->template_select_limit;

        return parent::limit($cnt, $shift);
    }

    public function _render_limit_start()
    {
        return (int)$this->args['limit']['shift'];
    }

    public function _render_and_limit_end()
    {
        if (!$this->args['limit']['cnt']) {
            return '';
        }
        return ' and __dsql_rownum<'.
            ((int)($this->args['limit']['cnt'] + $this->args['limit']['shift']));
    }

    public function _escape($value)
    {
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        return '"'.$value.'"';
    }
    protected function _escapeSoft($value)
    {
        // supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        // in some cases we should not escape
        if ($this->isUnescapablePattern($value)) {
            return $value;
        }

        if (is_string($value) && strpos($value, '.') !== false) {
            return implode('.', array_map(__METHOD__, explode('.', $value)));
        }

        return '"'.trim($value).'"';
    }
}
