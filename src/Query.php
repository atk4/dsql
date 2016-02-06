<?php

namespace atk4\dsql;

/**
 * @todo Class description goes here
 */
class Query
{
    /**
     * Define templates for the basic queries
     */
    public $templates = [
        'select' => 'select [field] [from] [table]',
    ];

    /**
     * Hash containing configuration accumulated by calling methods
     * such as field(), table(), etc
     */
    private $args = [];

    /**
     * If no fields are defined, this field is used
     */
    public $defaultField = '*';

    /**
     * Backticks are added around all fields. Set this to blank string to avoid
     */
    public $escapeChar = '`';

    /**
     * Specifying options to constructors will override default
     * attribute values of this class
     *
     * @param array $options will initialize class properties
     */
    function __construct($options = array())
    {
        foreach ($options as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Adds new column to resulting select by querying $field.
     *
     * Examples:
     *  $q->field('name');
     *
     * Second argument specifies table for regular fields
     *  $q->field('name', 'user');
     *  $q->field('name', 'user')->field('line1', 'address');
     *
     * Array as a first argument will specify multiple fields, same as calling field() multiple times
     *  $q->field(['name', 'surname']);
     *
     * Associative array will assume that "key" holds the field alias.
     * Value may be field name, expression or Query object itself
     *  $q->field(['alias' => 'name', 'alias2' => 'surname']);
     *  $q->field(['alias' => $q->expr(..), 'alias2' => $q->dsql()->.. ]);
     *
     * You may use array with aliases together with table specifier.
     *  $q->field(['alias' => 'name', 'alias2' => 'surname'], 'user');
     *
     * You can specify $q->expr() for calculated fields. In such case field alias is mandatory
     *  $q->field( $q->expr('2+2'), 'alias');   // must always use alias
     *
     * You can use $q->dsql() for subqueries. In such case field alias is mandatory
     *  $q->field( $q->dsql()->table('x')... , 'alias');    // must always use alias
     *
     * @param string|array $field Specifies field to select
     * @param string       $table Specify if not using primary table
     * @param string       $alias Specify alias for this field
     *
     * @return Query $this
     */
    function field($field, $table = null, $alias = null)
    {
        // field is passed as string, may contain commas
        if (is_string($field) && strpos($field, ',') !== false) {
            $field = explode(',', $field);
        } elseif (is_object($field)) {
            $alias = $table;
            $table = null;
        }

        // recursively add array fields
        if (is_array($field)) {
            foreach ($field as $alias => $f) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                $this->field($f, $table, $alias);
            }
            return $this;
        }
        $this->args['fields'][] = [$field, $table, $alias];
        return $this;
    }

    /**
     * Returns template component for [field]
     *
     * @return string Parsed template chunk
     */
    function _render_field()
    {
        // will be joined for output
        $result = [];

        // If no fields were defined, use defaultField
        if (!isset($this->args['fields']) || !($this->args['fields'])) {
            if ($this->defaultField instanceof DB_dsql) {
                return $this->_consume($this->defaultField);
            }
            return (string)$this->defaultField;
        }

        foreach ($this->args['fields'] as $row) {
            list($field,$table,$alias) = $row;

            // Do not use alias, if it's same as field
            if ($alias === $field) {
                $alias = null;
            }

            // Will parameterize the value and backtick if necessary.
            $field = $this->_consume($field);

            // TODO: Commented until I figure out what this does
            /*
            if (!$field) {
                $field = $table;
                $table = null;
            }
            */

            if (!is_null($table)) {
                // table name cannot be expression, so only backtick
                $field = $this->_escape($table) . '.' . $field;
            }

            if ($alias && $alias !== null) {
                // field alias cannot be expression, so only backtick
                $field .= ' ' . $this->_escape($alias);
            }
            $result[] = $field;
        }
        return join(',', $result);
    }

    /**
     * Recursively renders sub-query or expression, combining parameters.
     * If the argument is more likely to be a field, use tick=true
     *
     * @param object|string $dsql Expression
     * @param boolean       $tick Preferred quoted style
     *
     * @return string Quoted expression
     */
    function _consume($sql_code)
    {
        if ($sql_code === null) {
            return null;
        }

        /** TODO: TEMPORARILY removed, ATK feature, implement with traits **/
        /*
        if (is_object($sql_code) && $sql_code instanceof Field) {
            $sql_code = $sql_code->getExpr();
        }
        */
        if (!is_object($sql_code) || !$sql_code instanceof Query) {
            return $this->_escape($sql_code);
        }
        $sql_code->params = &$this->params;
        $ret = $sql_code->_render();
        if ($sql_code->mode === 'select') {
            $ret = '(' . $ret . ')';
        }
        unset($sql_code->params);
        $sql_code->params = [];
        
        return $ret;
    }

    /**
     * Escapes argument by adding backticks around it.
     * This will allow you to use reserved SQL words as table or field names
     * such as "table"
     *
     * @param string $sql_code Any string
     *
     * @return string Quoted string
     */
    function _escape($sql_code)
    {
        // Supports array
        if (is_array($sql_code)) {
            $out = [];
            foreach ($sql_code as $s) {
                $out[] = $this->_escape($s);
            }
            return $out;
        }

        if (!$this->escapeChar
            || is_object($sql_code)
            || $sql_code === '*'
            || strpos($sql_code, '.') !== false
            || strpos($sql_code, '(') !== false
            || strpos($sql_code, $this->escapeChar) !== false
        ) {
            return $sql_code;
        }

        return $this->escapeChar . $sql_code . $this->escapeChar;
    }

    /**
     * @todo Method description
     */
    public function table($table)
    {
        return (boolean)$table;
    }

    /**
     * @todo Method description
     */
    public function render()
    {

    }
}
