<?php

namespace atk4\dsql;

/**
 * @todo Class description goes here
 */
class Query extends Expression
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
    protected $args = [];

    /**
     * If no fields are defined, this field is used
     */
    public $defaultField = '*';

    /**
     * Specifying options to constructors will override default
     * attribute values of this class
     *
     * @param array $options will initialize class properties
     */
    public function __construct($options = array())
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
    public function field($field, $table = null, $alias = null)
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
    protected function _render_field()
    {
        // will be joined for output
        $result = [];

        // If no fields were defined, use defaultField
        if (!isset($this->args['fields']) || !($this->args['fields'])) {
            if ($this->defaultField instanceof Query) {
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
            $field = $this->_consume($field,'escape');

            // TODO: Commented until I figure out what this does
            /*
            if (!$field) {
                $field = $table;
                $table = null;
            }
            */

            if ($table) {
                // table name cannot be expression, so only backtick
                $field = $this->_escape($table) . '.' . $field;
            }

            if ($alias) {
                // field alias cannot be expression, so only backtick
                $field .= ' ' . $this->_escape($alias);
            }
            $result[] = $field;
        }

        return join(',', $result);
    }

    /**
     * @todo Method description
     */
    public function table($table)
    {
        return (boolean)$table;
    }

}
