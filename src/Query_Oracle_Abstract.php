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
    protected function _render_field_noalias()
    {
        // will be joined for output
        $ret = [];

        // If no fields were defined, use defaultField
        if (empty($this->args['field'])) {
            if ($this->defaultField instanceof Expression) {
                return $this->_consume($this->defaultField);
            }

            return (string) $this->defaultField;
        }

        // process each defined field
        foreach ($this->args['field'] as $alias => $field) {
            // Do not use alias

            // Will parameterize the value and backtick if necessary
            $field = $this->_consume($field, 'soft-escape');

            $ret[] = $field;
        }

        return implode(',', $ret);
    }
}
