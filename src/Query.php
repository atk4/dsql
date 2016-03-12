<?php // vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on SQL server (such as select, insert, delete, etc)
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
     * Query will use one of the predefined "templates". The mode
     * will contain name of template used.
     *
     * @var [type]
     */
    public $mode = null;

    /**
     * Hash containing configuration accumulated by calling methods
     * such as field(), table(), etc
     */
    protected $args = [];

    /**
     * If no fields are defined, this field is used
     */
    public $defaultField = '*';


    // {{{ Field specification and rendering 
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
    // }}}

    // {{{ Table specification and rendering
    protected $main_table = null;

    /**
     * @todo Method description
     */
    public function table($table, $alias = null)
    {
        if (is_array($table)) {
            // array_map([$this, 'table'], $table); ??

            foreach ($table as $alias => $t) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                $this->table($t, $alias);
            }
            return $this;
        }

        // This can be expression, but then we can only set the table once
        if ($table instanceof Expression) {

            if (isset($this->args['table'])){
                throw new Exception('You cannot use table([expression]). table() was called before.');
            }


            $this->main_table = false;
            $this->args['table'] = $table;
            return $this;
        }

        if (!isset($this->args['table'])) {
            $this->args['table'] = array();
        }

        if ($this->args['table'] instanceof Expression){
            throw new Exception('You cannot use table(). You have already used table([expression]) previously.');
        }

        // main_table will be set only if table() is called once. It's used
        // wher joining with other tables
        if ($this->main_table === null) {
            $this->main_table = $alias ? $alias : $table;

            // on multiple calls, main_table will be false and we won't
            // be able to join easily anymore.
        } elseif ($this->main_table) {
            $this->main_table = false;   // query from multiple tables
        }

        $this->args['table'][] = array($table, $alias);

        return $this;
    }

    /**
     * Renders part of the template: [table]
     * Do not call directly.
     *
     * @return string Parsed template chunk
     */
    protected function _render_table()
    {
        $ret = array();
        if (!isset($this->args['table'])){
            return '';
        }

        if ($this->args['table'] instanceof Expression) {

            // This will wrap into () if Query is used here
            return $this->_consume($this->args['table']);
        }

        foreach ($this->args['table'] as $row) {
            list($table, $alias) = $row;

            $table = $this->_escape($table);

            if ($alias !== null) {
                $table .= ' '.$this->_escape($alias);
            }

            $ret[] = $table;
        }

        return implode(',', $ret);
    }

    protected function _render_from()
    {
        return isset($this->main_table)?'from':'';
    }
    /// }}}

    // {{{ Miscelanious
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
     * When rendering a query, if the template is not set explicitly will use "select" mode
     * @return [type] [description]
     */
    public function render()
    {
        if (!$this->template) {
            $this->selectTemplate('select');
        }
        return parent::render();

    }

    /**
     * Switch template for this query. Determines what would be done
     * on execute.
     *
     * By default it is in SELECT mode
     *
     * @param string $mode A key for $this->sql_templates
     *
     * @return DB_dsql $this
     */
    public function selectTemplate($mode)
    {
        $this->mode = $mode;
        $this->template = $this->templates[$mode];

        return $this;
    }
    /// }}}
}
