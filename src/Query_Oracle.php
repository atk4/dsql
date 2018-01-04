<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on Oracle server.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Query_Oracle extends Query_Oracle_Abstract
{
    // [limit] not supported. TODO - add rownum implementation instead
    protected $template_select = 'select[option] [field] [from] [table][join][where][group][having][order]';

    protected $template_select_limit = 'select [field] [from] (select[option] rownum "__dsql_rownum", [field_noalias] [from] [table][join][where][group][having][order]) where "__dsql_rownum">[limit_start][and_limit_end]';

    public function limit($cnt, $shift = null)
    {
        // This is for pre- 12c version
        $this->template_select = $this->template_select_limit;

        return parent::limit($cnt, $shift);
    }

    public function _render_limit_start()
    {
        return (int) $this->args['limit']['shift'];
    }

    public function _render_and_limit_end()
    {
        if (!$this->args['limit']['cnt']) {
            return '';
        }

        return ' and "__dsql_rownum"<='.
            ((int) ($this->args['limit']['cnt'] + $this->args['limit']['shift']));
    }

    public function field($field, $alias = null)
    {
        // field is passed as string, may contain commas
        if (is_string($field) && strpos($field, ',') !== false) {
            $field = explode(',', $field);
        }

        // recursively add array fields
        if (is_array($field)) {
            if ($alias !== null) {
                throw new Exception([
                    'Alias must not be specified when $field is an array',
                    'alias' => $alias,
                ]);
            }

            foreach ($field as $alias => $f) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                $this->field($f, $alias);
            }

            return $this;
        }

        // save field in args
        $this->_set_args('field', $alias, $field);

        return $this;
    }
}
