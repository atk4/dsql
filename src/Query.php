<?php // vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on SQL server (such as select, insert, delete, etc)
 */
class Query extends Expression
{
    /**
     * Define templates for the basic queries.
     *
     * @var array
     */
    public $templates = [
        'select' => 'select [field] [from] [table][where][having]',
        'delete' => 'delete [from] [table][where][having]',
        'insert' => 'insert into [table_noalias] ([set_fields]) values ([set_values])',
        'update' => 'update [table_noalias] set [set] [where]',
    ];

    /**
     * Query will use one of the predefined "templates". The mode
     * will contain name of template used.
     *
     * @var string
     */
    public $mode = null;

    /**
     * If no fields are defined, this field is used
     *
     * @var string
     */
    public $defaultField = '*';

    /**
     * Name of database table to use in query.
     * Will be used only if we query from one table.
     *
     * @var null|false|string
     */
    protected $main_table = null;


    // TODO: I want to get rid of $table argument, because it's either
    // can embedded into a field directly or not necessary for expression,

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
     * @param mixed  $field Specifies field to select
     * @param string $table Specify if not using primary table
     * @param string $alias Specify alias for this field
     *
     * @return $this
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
     * Returns template component for [field].
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
            list($field, $table, $alias) = $row;

            // Do not use alias, if it's same as field
            if ($alias === $field) {
                $alias = null;
            }

            // Will parameterize the value and backtick if necessary.
            $field = $this->_consume($field, 'escape');

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

        return implode(',', $result);
    }
    // }}}

    // {{{ Table specification and rendering
    /**
     * @todo Method description
     *
     * @param mixed  $table
     * @param string $alias
     *
     * @return $this
     */
    public function table($table, $alias = null)
    {
        if (is_array($table)) {

            if ($alias !== null) {
                throw new Exception('Do not use alias with multiple tables in '.__METHOD__);
            }

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

            if (isset($this->args['table'])) {
                throw new Exception('You cannot use table([expression]). table() was called before. In '.__METHOD__);
            }

            $this->main_table = false;
            $this->args['table'] = $table;
            return $this;
        }

        // comma-separating tables
        if (is_string($table) && strpos($table, ',') !== false) {
            if ($alias !== null) {
                throw new Exception('Do not use alias with multiple tables in '.__METHOD__);
            }
            return $this->table(explode(',', $table));
        }

        if (!isset($this->args['table'])) {
            $this->args['table'] = array();
        }

        if ($this->args['table'] instanceof Expression) {
            throw new Exception('You cannot use table(). You have already used table([expression]) previously. In '.__METHOD__);
        }

        // main_table will be set only if table() is called once. It's used
        // when joining with other tables
        if ($this->main_table === null) {
            $this->main_table = $alias ?: $table;

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
        if (!isset($this->args['table'])) {
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
                $table .= ' ' . $this->_escape($alias);
            }

            $ret[] = $table;
        }

        return implode(',', $ret);
    }

    /**
     * Renders part of the template: [table_noalias]
     * Do not call directly.
     *
     * @return string Parsed template chunk
     */
    protected function _render_table_noalias()
    {
        $ret = array();

        if ($this->args['table'] instanceof Expression) {
            throw new Exception('Table cannot be expression for UPDATE / INSERT queries in '.__METHOD__);
        }

        foreach ($this->args['table'] as $row) {
            list($table, $alias) = $row;

            $table = $this->_escape($table);

            $ret[] = $table;
        }

        return implode(', ', $ret);
    }

    /**
     * Renders part of the template: [from]
     * Do not call directly.
     *
     * @return string Parsed template chunk
     */
    protected function _render_from()
    {
        return isset($this->main_table) ? 'from' : '';
    }
    /// }}}

    // {{{ where() and having() specification and rendering
    /**
     * Adds condition to your query.
     *
     * Examples:
     *  $q->where('id',1);
     *
     * By default condition implies equality. You can specify a different comparison
     * operator by eithre including it along with the field or using 3-argument
     * format:
     *  $q->where('id>','1');
     *  $q->where('id','>',1);
     *
     * You may use Expression as any part of the query.
     *  $q->where($q->expr('a=b'));
     *  $q->where('date>',$q->expr('now()'));
     *  $q->where($q->expr('length(password)'),'>',5);
     *
     * If you specify Query as an argument, it will be automatically
     * surrounded by brackets:
     *  $q->where('user_id',$q->dsql()->table('users')->field('id'));
     *
     * You can specify OR conditions by passing single argument - array:
     *  $q->where([
     *      ['a','is',null],
     *      ['b','is',null]
     *  ]);
     *
     * If entry of the OR condition is not an array, then it's assumed to
     * be an expression;
     *
     *  $q->where([
     *      ['age',20],
     *      'age is null'
     *  ]);
     *
     * The above use of OR conditions rely on orExpr() functionality. See
     * that method for morer information, but in short it makes use of
     * Expression_OR class
     *
     * To specify OR conditions
     *  $q->where($q->orExpr()->where('a',1)->where('b',1));
     *
     * @param mixed  $field     Field, array for OR or Expression
     * @param string $cond      Condition such as '=', '>' or 'is not'
     * @param string $value     Value. Will be quoted unless you pass expression
     * @param string $kind      Do not use directly. Use having()
     * @param string $num_args  When $kind is passed, we can't determine number of
     *                          actual arguments, so this argumen must be specified.
     *
     * @return $this
     */
    public function where($field, $cond = null, $value = null, $kind = 'where', $num_args = null)
    {
        // Number of passed arguments will be used to determine if arguments were specified or not
        if (is_null($num_args)) {
            $num_args = func_num_args();
        }

        // Array as first argument means we have to replace it with orExpr()
        if (is_array($field)) {
            // or conditions
            $or = $this->orExpr();
            foreach ($field as $row) {
                if (is_array($row)) {
                    call_user_func_array([$or, 'where'], $row);
                } else {
                    $or->where($row);
                }
            }
            $field = $or;
        }

        if ($num_args === 1 && is_string($field)) {
            $this->args[$kind][] = [$this->expr($field)];
            return $this;
        }

        // first argument is string containing more than just a field name and no more than 2
        // arguments means that we either have a string expression or embedded condition.
        if (is_string($field) && !preg_match('/^[.a-zA-Z0-9_]*$/', $field) && $num_args === 2) {
            // field contains non-alphanumeric values. Look for condition
            preg_match(
                '/^([^ <>!=]*)([><!=]*|( *(not|is|in|like))*) *$/',
                $field,
                $matches
            );

            // matches[2] will contain the condition, but $cond will contain the value
            $value = $cond;
            $cond = $matches[2];

            // if we couldn't clearly identify the condinulltion, we might be dealing with
            // a more complex expression. If expression is followed by another argument
            // we need to add equation sign  where('now()',123).
            if (!$cond) {
                $matches[1] = $this->expr($field);

                if ($num_args == 2) {
                    $cond = '=';
                } else {
                    $cond = null;
                }
            } else {
                $num_args++;
            }

            $field = $matches[1];
        }


        switch ($num_args) {
            case 1:
                $this->args[$kind][] = [$field];
                break;
            case 2:
                $this->args[$kind][] = [$field, $cond];
                break;
            case 3:
                $this->args[$kind][] = [$field, $cond, $value];
                break;
        }

        return $this;
    }

    /**
     * Same syntax as where().
     *
     * @param mixed  $field Field, array for OR or Expression
     * @param string $cond  Condition such as '=', '>' or 'is not'
     * @param string $value Value. Will be quoted unless you pass expression
     *
     * @return $this
     */
    public function having($field, $cond = null, $value = null)
    {
        $num_args = func_num_args();

        return $this->where($field, $cond, $value, 'having', $num_args);
    }

    /**
     * Subroutine which renders either [where] or [having].
     *
     * @param string $kind 'where' or 'having'
     *
     * @return array Parsed chunks of query
     */
    protected function __render_where($kind)
    {
        $ret = [];

        // where() might have been called multiple times. Collect all conditions,
        // then join with them AND keyword
        foreach ($this->args[$kind] as $row) {

            if (count($row) === 3) {
                list($field, $cond, $value) = $row;
            } elseif (count($row) === 2) {
                list($field, $cond) = $row;
            } elseif (count($row) === 1) {
                list($field) = $row;
            }

            if (is_object($field)) {
                // if first argument is object/expression, consume it, converting
                // it to the string
                $field = $this->_consume($field);
            } else {
                // otherwise, perform some escaping
                $field = implode('.', $this->_escape(explode('.', $field)));
            }

            if (count($row) == 1) {
                // Only a single parameter was passed, so we simply include all
                $ret[] = $field;
                continue;
            }

            // below are only cases when 2 or 3 arguments are passed

            // if no condition defined - set default condition
            if (count($row) == 2) {
                $value = $cond;
                if (is_array($value)) {
                    $cond = 'in';
                } elseif (is_object($value) && $value->mode === 'select') {
                    $cond = 'in';
                } else {
                    $cond = '=';
                }
            } else {
                $cond = trim(strtolower($cond));
            }

            // below we can be sure that all 3 arguments has been passed

            if ($value === null) {
                // special conditions if value is null
                if ($cond === '=') {
                    $cond = 'is';
                } elseif (in_array($cond, array('!=', '<>', 'not'))) {
                    $cond = 'is not';
                }
            }

            // value should be array for such conditions
            if (($cond === 'in' || $cond === 'not in') && is_string($value)) {
                $value = explode(',', $value);
            }

            // if value is array, then use IN or NOT IN as condition
            if (is_array($value)) {
                $value = '('.implode(',', $this->_param($value)).')';
                $cond = in_array($cond, array('!=', '<>', 'not', 'not in')) ? 'not in' : 'in';
                $ret[] = $this->_consume($field, 'escape').' '.$cond.' '.$value;
                continue;
            }

            // if value is object, then it should be DSQL itself
            // otherwise just escape value
            $value = $this->_consume($value, 'param');
            $ret[] = $field.' '.$cond.' '.$value;
        }

        return $ret;
    }

    /**
     * Renders [where].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_where()
    {
        if (!isset($this->args['where'])) {
            return;
        }

        return ' where '.implode(' and ', $this->__render_where('where'));
    }

    /**
     * Renders [orwhere].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_orwhere()
    {
        if (!isset($this->args['where'])) {
            return;
        }

        return implode(' or ', $this->__render_where('where'));
    }

    /**
     * Renders [andwhere].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_andwhere()
    {
        if (!isset($this->args['where'])) {
            return;
        }

        return implode(' and ', $this->__render_where('where'));
    }

    /**
     * Renders [having].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_having()
    {
        if (!isset($this->args['having'])) {
            return;
        }

        return ' having '.implode(' and ', $this->__render_where('having'));
    }
    // }}}

    // {{{ Set field implementation
    /**
     * Sets field value for INSERT or UPDATE statements.
     *
     * @param string|array $field Name of the field
     * @param mixed  $value Value of the field
     *
     * @return $this
     */
    public function set($field, $value = null)
    {
        if ($value === false) {
            throw new Exception('Value "false" is not supported by SQL for field '.$field.' in '.__METHOD__);
        }

        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->set($key, $value);
            }

            return $this;
        }

        $this->args['set'][$field] = $value;

        return $this;
    }

    /**
     * Renders [set] for UPDATE query.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set()
    {
        $result = array();
        if ($this->args['set']) {
            foreach ($this->args['set'] as $field => $value) {
                $field = $this->_consume($field, 'escape');
                $value = $this->_consume($value, 'param');

                $result[] = $field.'='.$value;
            }
        }

        return implode(', ', $result);
    }

    /**
     * Renders [set_fields] for INSERT.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set_fields()
    {
        $result = array();
        if ($this->args['set']) {
            foreach ($this->args['set'] as $field => $value) {
                $field = $this->_consume($field, 'escape');
                $field = $this->_consume($field, 'escape');

                $result[] = $field;
            }
        }

        return implode(',', $result);
    }

    /**
     * Renders [set_values] for INSERT.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set_values()
    {
        $result = array();
        if ($this->args['set']) {
            foreach ($this->args['set'] as $field => $value) {
                $value = $this->_consume($value, 'param');

                $result[] = $value;
            }
        }

        return implode(',', $result);
    }
    /// }}}

    // {{{ Miscelanious
    /**
     * Renders query template. If the template is not explicitly set will use "select" mode.
     *
     * @return string
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
     * @return $this
     */
    public function selectTemplate($mode)
    {
        $this->mode = $mode;
        $this->template = $this->templates[$mode];

        return $this;
    }

    /**
     * Use this instead of "new Query()" if you want to automatically bind
     * query to the same connection as the parent.
     *
     * @param array $properties
     *
     * @return Query
     */
    public function dsql($properties = [])
    {
        $properties['connection'] = $this->connection;
        
        return new Query($properties);
    }

    /**
     * Returns new Query object of [or] expression.
     *
     * @return Query
     */
    public function orExpr()
    {
        return new Query(['template' => '[orwhere]']);
    }

    /**
     * Returns new Query object of [and] expression.
     *
     * @return Query
     */
    public function andExpr()
    {
        return new Query(['template' => '[andwhere]']);
    }
    /// }}}
}
