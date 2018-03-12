<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Perform query operation on SQL server (such as select, insert, delete, etc).
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Query extends Expression
{
    /**
     * Query will use one of the predefined templates. The $mode will contain
     * name of template used. Basically it's part of Query property name -
     * Query::template_[$mode].
     *
     * @var string
     */
    public $mode = 'select';

    /**
     * If no fields are defined, this field is used.
     *
     * @var string|Expression
     */
    public $defaultField = '*';

    /**
     * SELECT template.
     *
     * @var string
     */
    protected $template_select = 'select[option] [field] [from] [table][join][where][group][having][order][limit]';

    /**
     * INSERT template.
     *
     * @var string
     */
    protected $template_insert = 'insert[option] into [table_noalias] ([set_fields]) values ([set_values])';

    /**
     * REPLACE template.
     *
     * @var string
     */
    protected $template_replace = 'replace[option] into [table_noalias] ([set_fields]) values ([set_values])';

    /**
     * DELETE template.
     *
     * @var string
     */
    protected $template_delete = 'delete [from] [table_noalias][where][having]';

    /**
     * UPDATE template.
     *
     * @var string
     */
    protected $template_update = 'update [table_noalias] set [set] [where]';

    /**
     * TRUNCATE template.
     *
     * @var string
     */
    protected $template_truncate = 'truncate table [table_noalias]';

    /**
     * Name or alias of base table to use when using default join().
     *
     * This is set by table(). If you are using multiple tables,
     * then $main_table is set to false as it is irrelevant.
     *
     * @var null|false|string
     */
    protected $main_table = null;

    // {{{ Field specification and rendering

    /**
     * Adds new column to resulting select by querying $field.
     *
     * Examples:
     *  $q->field('name');
     *
     * You can use a dot to prepend table name to the field:
     *  $q->field('user.name');
     *  $q->field('user.name')->field('address.line1');
     *
     * Array as a first argument will specify multiple fields, same as calling field() multiple times
     *  $q->field(['name', 'surname', 'address.line1']);
     *
     * You can pass first argument as Expression or Query
     *  $q->field( $q->expr('2+2'), 'alias');   // must always use alias
     *
     * You can use $q->dsql() for subqueries. Subqueries will be wrapped in
     * brackets.
     *  $q->field( $q->dsql()->table('x')... , 'alias');
     *
     * Associative array will assume that "key" holds the field alias.
     * Value may be field name, Expression or Query.
     *  $q->field(['alias' => 'name', 'alias2' => 'mother.surname']);
     *  $q->field(['alias' => $q->expr(..), 'alias2' => $q->dsql()->.. ]);
     *
     * If you need to use funky name for the field (e.g, one containing
     * a dot or a space), you should wrap it into expression:
     *  $q->field($q->expr('{}', ['fun...ky.field']), 'f');
     *
     * @param mixed  $field Specifies field to select
     * @param string $alias Specify alias for this field
     *
     * @return $this
     */
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

    /**
     * Returns template component for [field].
     *
     * @param bool $add_alias Should we add aliases, see _render_field_noalias()
     *
     * @return string Parsed template chunk
     */
    protected function _render_field($add_alias = true)
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
            // Do not add alias, if:
            //  - we don't want aliases OR
            //  - alias is the same as field OR
            //  - alias is numeric
            if (
                $add_alias === false
                || (is_string($field) && $alias === $field)
                || is_numeric($alias)
            ) {
                $alias = null;
            }

            // Will parameterize the value and escape if necessary
            $field = $this->_consume($field, 'soft-escape');

            if ($alias) {
                // field alias cannot be expression, so simply escape it
                $field .= ' '.$this->_escape($alias);
            }

            $ret[] = $field;
        }

        return implode(',', $ret);
    }

    /**
     * Renders part of the template: [field_noalias]
     * Do not call directly.
     *
     * @return string Parsed template chunk
     */
    protected function _render_field_noalias()
    {
        return $this->_render_field(false);
    }

    // }}}

    // {{{ Table specification and rendering

    /**
     * Specify a table to be used in a query.
     *
     * @param mixed  $table Specifies table
     * @param string $alias Specify alias for this table
     *
     * @return $this
     */
    public function table($table, $alias = null)
    {
        // comma-separated table names
        if (is_string($table) && strpos($table, ',') !== false) {
            $table = explode(',', $table);
        }

        // array of tables - recursively process each
        if (is_array($table)) {
            if ($alias !== null) {
                throw new Exception([
                    'You cannot use single alias with multiple tables',
                    'alias' => $alias,
                ]);
            }

            foreach ($table as $alias => $t) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                $this->table($t, $alias);
            }

            return $this;
        }

        // if table is set as sub-Query, then alias is mandatory
        if ($table instanceof self && $alias === null) {
            throw new Exception('If table is set as Query, then table alias is mandatory');
        }

        if (is_string($table) && $alias === null) {
            $alias = $table;
        }

        // main_table will be set only if table() is called once.
        // it's used as "default table" when joining with other tables, see join().
        // on multiple calls, main_table will be false and we won't
        // be able to join easily anymore.
        $this->main_table = ($this->main_table === null && $alias !== null ? $alias : false);

        // save table in args
        $this->_set_args('table', $alias, $table);

        return $this;
    }

    /**
     * Renders part of the template: [table]
     * Do not call directly.
     *
     * @param bool $add_alias Should we add aliases, see _render_table_noalias()
     *
     * @return string Parsed template chunk
     */
    protected function _render_table($add_alias = true)
    {
        // will be joined for output
        $ret = [];

        if (empty($this->args['table'])) {
            return '';
        }

        // process tables one by one
        foreach ($this->args['table'] as $alias => $table) {

            // throw exception if we don't want to add alias and table is defined as Expression
            if ($add_alias === false && $table instanceof self) {
                throw new Exception('Table cannot be Query in UPDATE, INSERT etc. query modes');
            }

            // Do not add alias, if:
            //  - we don't want aliases OR
            //  - alias is the same as table name OR
            //  - alias is numeric
            if (
                $add_alias === false
                || (is_string($table) && $alias === $table)
                || is_numeric($alias)
            ) {
                $alias = null;
            }

            // consume or escape table
            $table = $this->_consume($table, 'soft-escape');

            // add alias if needed
            if ($alias) {
                $table .= ' '.$this->_escape($alias);
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
        return $this->_render_table(false);
    }

    /**
     * Renders part of the template: [from]
     * Do not call directly.
     *
     * @return string Parsed template chunk
     */
    protected function _render_from()
    {
        return empty($this->args['table']) ? '' : 'from';
    }

    /// }}}

    // {{{ join()

    /**
     * Joins your query with another table. Join will use $main_table
     * to reference the main table, unless you specify it explicitly.
     *
     * Examples:
     *  $q->join('address');         // on user.address_id=address.id
     *  $q->join('address.user_id'); // on address.user_id=user.id
     *  $q->join('address a');       // With alias
     *  $q->join(array('a'=>'address')); // Also alias
     *
     * Second argument may specify the field of the master table
     *  $q->join('address', 'billing_id');
     *  $q->join('address.code', 'code');
     *  $q->join('address.code', 'user.code');
     *
     * Third argument may specify which kind of join to use.
     *  $q->join('address', null, 'left');
     *  $q->join('address.code', 'user.code', 'inner');
     *
     * Using array syntax you can join multiple tables too
     *  $q->join(array('a'=>'address', 'p'=>'portfolio'));
     *
     * You can use expression for more complex joins
     *  $q->join('address',
     *      $q->orExpr()
     *          ->where('user.billing_id=address.id')
     *          ->where('user.technical_id=address.id')
     *  )
     *
     * @param string|array $foreign_table  Table to join with
     * @param mixed        $master_field   Field in master table
     * @param string       $join_kind      'left' or 'inner', etc
     * @param string       $_foreign_alias Internal, don't use
     *
     * @return $this
     */
    public function join(
        $foreign_table,
        $master_field = null,
        $join_kind = null,
        $_foreign_alias = null
    ) {
        // If array - add recursively
        if (is_array($foreign_table)) {
            foreach ($foreign_table as $alias => $foreign) {
                if (is_numeric($alias)) {
                    $alias = null;
                }

                $this->join($foreign, $master_field, $join_kind, $alias);
            }

            return $this;
        }
        $j = [];

        // try to find alias in foreign table definition
        if ($_foreign_alias === null) {
            list($foreign_table, $_foreign_alias) = array_pad(explode(' ', $foreign_table, 2), 2, null);
        }

        // Split and deduce fields
        list($f1, $f2) = array_pad(explode('.', $foreign_table, 2), 2, null);

        if (is_object($master_field)) {
            $j['expr'] = $master_field;
        } else {
            // Split and deduce primary table
            if ($master_field === null) {
                list($m1, $m2) = [null, null];
            } else {
                list($m1, $m2) = array_pad(explode('.', $master_field, 2), 2, null);
            }
            if ($m2 === null) {
                $m2 = $m1;
                $m1 = null;
            }
            if ($m1 === null) {
                $m1 = $this->main_table;
            }

            // Identify fields we use for joins
            if ($f2 === null && $m2 === null) {
                $m2 = $f1.'_id';
            }
            if ($m2 === null) {
                $m2 = 'id';
            }
            $j['m1'] = $m1;
            $j['m2'] = $m2;
        }

        $j['f1'] = $f1;
        if ($f2 === null) {
            $f2 = 'id';
        }
        $j['f2'] = $f2;

        $j['t'] = $join_kind ?: 'left';
        $j['fa'] = $_foreign_alias;

        $this->args['join'][] = $j;

        return $this;
    }

    /**
     * Renders [join].
     *
     * @return string rendered SQL chunk
     */
    public function _render_join()
    {
        if (!isset($this->args['join'])) {
            return '';
        }
        $joins = [];
        foreach ($this->args['join'] as $j) {
            $jj = '';

            $jj .= $j['t'].' join ';

            $jj .= $this->_escape($j['f1']);

            if ($j['fa'] !== null) {
                $jj .= ' as '.$this->_escape($j['fa']);
            }

            $jj .= ' on ';

            if (isset($j['expr'])) {
                $jj .= $this->_consume($j['expr']);
            } else {
                $jj .=
                    $this->_escape($j['fa'] ?: $j['f1']).'.'.
                    $this->_escape($j['f2']).' = '.
                    ($j['m1'] === null ? '' : $this->_escape($j['m1']).'.').
                    $this->_escape($j['m2']);
            }
            $joins[] = $jj;
        }

        return ' '.implode(' ', $joins);
    }

    // }}}

    // {{{ where() and having() specification and rendering

    /**
     * Adds condition to your query.
     *
     * Examples:
     *  $q->where('id',1);
     *
     * By default condition implies equality. You can specify a different comparison
     * operator by either including it along with the field or using 3-argument
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
     * that method for more information.
     *
     * To specify OR conditions
     *  $q->where($q->orExpr()->where('a',1)->where('b',1));
     *
     * @param mixed  $field    Field, array for OR or Expression
     * @param mixed  $cond     Condition such as '=', '>' or 'is not'
     * @param mixed  $value    Value. Will be quoted unless you pass expression
     * @param string $kind     Do not use directly. Use having()
     * @param string $num_args When $kind is passed, we can't determine number of
     *                         actual arguments, so this argument must be specified.
     *
     * @return $this
     */
    public function where($field, $cond = null, $value = null, $kind = 'where', $num_args = null)
    {
        // Number of passed arguments will be used to determine if arguments were specified or not
        if ($num_args === null) {
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
        if ($num_args === 2 && is_string($field) && !preg_match('/^[.a-zA-Z0-9_]*$/', $field)) {
            // field contains non-alphanumeric values. Look for condition
            preg_match(
                '/^([^ <>!=]*)([><!=]*|( *(not|is|in|like))*) *$/',
                $field,
                $matches
            );

            // matches[2] will contain the condition, but $cond will contain the value
            $value = $cond;
            $cond = $matches[2];

            // if we couldn't clearly identify the condition, we might be dealing with
            // a more complex expression. If expression is followed by another argument
            // we need to add equation sign  where('now()',123).
            if (!$cond) {
                $matches[1] = $this->expr($field);

                $cond = '=';
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
                if (is_object($cond) && !$cond instanceof Expressionable && !$cond instanceof Expression) {
                    throw new Exception([
                        'Value cannot be converted to SQL-compatible expression',
                        'field'=> $field,
                        'value'=> $cond,
                    ]);
                }

                $this->args[$kind][] = [$field, $cond];
                break;
            case 3:
                if (is_object($value) && !$value instanceof Expressionable && !$value instanceof Expression) {
                    throw new Exception([
                        'Value cannot be converted to SQL-compatible expression',
                        'field'=> $field,
                        'cond' => $cond,
                        'value'=> $value,
                    ]);
                }

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
        // will be joined for output
        $ret = [];

        // where() might have been called multiple times. Collect all conditions,
        // then join them with AND keyword
        foreach ($this->args[$kind] as $row) {
            $ret[] = $this->__render_condition($row);
        }

        return $ret;
    }

    /**
     * Renders one condition.
     *
     * @param array $row Condition
     *
     * @return string
     */
    protected function __render_condition($row)
    {
        if (count($row) === 3) {
            list($field, $cond, $value) = $row;
        } elseif (count($row) === 2) {
            list($field, $cond) = $row;
        } elseif (count($row) === 1) {
            list($field) = $row;
        }

        $field = $this->_consume($field, 'soft-escape');

        if (count($row) == 1) {
            // Only a single parameter was passed, so we simply include all
            return $field;
        }

        // below are only cases when 2 or 3 arguments are passed

        // if no condition defined - set default condition
        if (count($row) == 2) {
            $value = $cond;

            if (is_array($value)) {
                $cond = 'in';
            } elseif ($value instanceof self && $value->mode === 'select') {
                $cond = 'in';
            } else {
                $cond = '=';
            }
        } else {
            $cond = trim(strtolower($cond));
        }

        // below we can be sure that all 3 arguments has been passed

        // special conditions (IS | IS NOT) if value is null
        if ($value === null) {
            if ($cond === '=') {
                $cond = 'is';
            } elseif (in_array($cond, ['!=', '<>', 'not'])) {
                $cond = 'is not';
            }
        }

        // value should be array for such conditions
        if (in_array($cond, ['in', 'not in', 'not']) && is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        // special conditions (IN | NOT IN) if value is array
        if (is_array($value)) {
            $value = '('.implode(',', $this->_param($value)).')';
            $cond = in_array($cond, ['!=', '<>', 'not', 'not in']) ? 'not in' : 'in';

            return $field.' '.$cond.' '.$value;
        }

        // if value is object, then it should be Expression or Query itself
        // otherwise just escape value
        $value = $this->_consume($value, 'param');

        return $field.' '.$cond.' '.$value;
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

    // {{{ group()

    /**
     * Implements GROUP BY functionality. Simply pass either field name
     * as string or expression.
     *
     * @param mixed $group Group by this
     *
     * @return $this
     */
    public function group($group)
    {
        // Case with comma-separated fields
        if (is_string($group) && !$this->isUnescapablePattern($group) && strpos($group, ',') !== false) {
            $group = explode(',', $group);
        }

        if (is_array($group)) {
            foreach ($group as $g) {
                $this->args['group'][] = $g;
            }

            return $this;
        }

        $this->args['group'][] = $group;

        return $this;
    }

    /**
     * Renders [group].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_group()
    {
        if (!isset($this->args['group'])) {
            return '';
        }

        $g = array_map(function ($a) {
            return $this->_consume($a, 'soft-escape');
        }, $this->args['group']);

        return ' group by '.implode(', ', $g);
    }

    // }}}

    // {{{ Set field implementation

    /**
     * Sets field value for INSERT or UPDATE statements.
     *
     * @param string|array $field Name of the field
     * @param mixed        $value Value of the field
     *
     * @return $this
     */
    public function set($field, $value = null)
    {
        if ($value === false) {
            throw new Exception([
                'Value "false" is not supported by SQL',
                'field' => $field,
                'value' => $value,
            ]);
        }

        if (is_array($value)) {
            throw new Exception([
                'Array values are not supported by SQL',
                'field' => $field,
                'value' => $value,
            ]);
        }

        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->set($key, $value);
            }

            return $this;
        }

        if (is_string($field) || $field instanceof Expression || $field instanceof Expressionable) {
            $this->args['set'][] = [$field, $value];
        } else {
            throw new Exception([
                'Field name should be string or Expressionable',
                'field' => $field,
            ]);
        }

        return $this;
    }

    /**
     * Renders [set] for UPDATE query.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set()
    {
        // will be joined for output
        $ret = [];

        if (isset($this->args['set']) && $this->args['set']) {
            foreach ($this->args['set'] as list($field, $value)) {
                $field = $this->_consume($field, 'escape');
                $value = $this->_consume($value, 'param');

                $ret[] = $field.'='.$value;
            }
        }

        return implode(', ', $ret);
    }

    /**
     * Renders [set_fields] for INSERT.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set_fields()
    {
        // will be joined for output
        $ret = [];

        if ($this->args['set']) {
            foreach ($this->args['set'] as list($field/*, $value*/)) {
                $field = $this->_consume($field, 'escape');

                $ret[] = $field;
            }
        }

        return implode(',', $ret);
    }

    /**
     * Renders [set_values] for INSERT.
     *
     * @return string rendered SQL chunk
     */
    protected function _render_set_values()
    {
        // will be joined for output
        $ret = [];

        if ($this->args['set']) {
            foreach ($this->args['set'] as list(/*$field*/, $value)) {
                $value = $this->_consume($value, 'param');

                $ret[] = $value;
            }
        }

        return implode(',', $ret);
    }

    // }}}

    // {{{ Option

    /**
     * Set options for particular mode.
     *
     * @param mixed  $option
     * @param string $mode   select|insert|replace
     *
     * @return $this
     */
    public function option($option, $mode = 'select')
    {
        // Case with comma-separated options
        if (is_string($option) && strpos($option, ',') !== false) {
            $option = explode(',', $option);
        }

        if (is_array($option)) {
            foreach ($option as $opt) {
                $this->args['option'][$mode][] = $opt;
            }

            return $this;
        }

        $this->args['option'][$mode][] = $option;

        return $this;
    }

    /**
     * Renders [option].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_option()
    {
        if (!isset($this->args['option'][$this->mode])) {
            return '';
        }

        return ' '.implode(' ', $this->args['option'][$this->mode]);
    }

    // }}}

    // {{{ Query Modes

    /**
     * Execute select statement.
     *
     * @return PDOStatement
     */
    public function select()
    {
        return $this->mode('select')->execute();
    }

    /**
     * Execute insert statement.
     *
     * @return PDOStatement
     */
    public function insert()
    {
        return $this->mode('insert')->execute();
    }

    /**
     * Execute update statement.
     *
     * @return PDOStatement
     */
    public function update()
    {
        return $this->mode('update')->execute();
    }

    /**
     * Execute replace statement.
     *
     * @return PDOStatement
     */
    public function replace()
    {
        return $this->mode('replace')->execute();
    }

    /**
     * Execute delete statement.
     *
     * @return PDOStatement
     */
    public function delete()
    {
        return $this->mode('delete')->execute();
    }

    /**
     * Execute truncate statement.
     *
     * @return PDOStatement
     */
    public function truncate()
    {
        return $this->mode('truncate')->execute();
    }

    // }}}

    // {{{ Limit

    /**
     * Limit how many rows will be returned.
     *
     * @param int $cnt   Number of rows to return
     * @param int $shift Offset, how many rows to skip
     *
     * @return $this
     */
    public function limit($cnt, $shift = null)
    {
        $this->args['limit'] = [
            'cnt'   => $cnt,
            'shift' => $shift,
        ];

        return $this;
    }

    /**
     * Renders [limit].
     *
     * @return string rendered SQL chunk
     */
    public function _render_limit()
    {
        if (isset($this->args['limit'])) {
            return ' limit '.
                (int) $this->args['limit']['shift'].
                ', '.
                (int) $this->args['limit']['cnt'];
        }
    }

    // }}}

    // {{{ Order

    /**
     * Orders results by field or Expression. See documentation for full
     * list of possible arguments.
     *
     * $q->order('name');
     * $q->order('name desc');
     * $q->order('name desc, id asc')
     * $q->order('name',true);
     *
     * @param string|array $order Order by
     * @param string|bool  $desc  true to sort descending
     *
     * @return $this
     */
    public function order($order, $desc = null)
    {
        // Case with comma-separated fields or first argument being an array
        if (is_string($order) && strpos($order, ',') !== false) {
            $order = explode(',', $order);
        }

        if (is_array($order)) {
            if ($desc !== null) {
                throw new Exception(
                    'If first argument is array, second argument must not be used'
                );
            }
            foreach (array_reverse($order) as $o) {
                $this->order($o);
            }

            return $this;
        }

        // First argument may contain space, to divide field and ordering keyword.
        // Explode string only if ordering keyword is 'desc' or 'asc'.
        if ($desc === null && is_string($order) && strpos($order, ' ') !== false) {
            $_chunks = explode(' ', $order);
            $_desc = strtolower(array_pop($_chunks));
            if (in_array($_desc, ['desc', 'asc'])) {
                $order = implode(' ', $_chunks);
                $desc = $_desc;
            }
        }

        if (is_bool($desc)) {
            $desc = $desc ? 'desc' : '';
        } elseif (strtolower($desc) === 'asc') {
            $desc = '';
        } elseif ($desc && strtolower($desc) != 'desc') {
            throw new Exception([
                'Incorrect ordering keyword',
                'order by' => $order,
                'desc'     => $desc,
            ]);
        }

        $this->args['order'][] = [$order, $desc];

        return $this;
    }

    /**
     * Renders [order].
     *
     * @return string rendered SQL chunk
     */
    public function _render_order()
    {
        if (!isset($this->args['order'])) {
            return'';
        }

        $x = [];
        foreach ($this->args['order'] as $tmp) {
            list($arg, $desc) = $tmp;
            $x[] = $this->_consume($arg, 'soft-escape').($desc ? (' '.$desc) : '');
        }

        return ' order by '.implode(', ', array_reverse($x));
    }

    // }}}

    public function __debugInfo()
    {
        $arr = [
            'R'          => false,
            'mode'       => $this->mode,
            //'template'   => $this->template,
            //'params'     => $this->params,
            //'connection' => $this->connection,
            //'main_table' => $this->main_table,
            //'args'       => $this->args,
        ];

        try {
            $arr['R'] = $this->getDebugQuery();
        } catch (\Exception $e) {
            $arr['R'] = $e->getMessage();
        }

        return $arr;
    }

    // {{{ Miscelanious

    /**
     * Renders query template. If the template is not explicitly set will use "select" mode.
     *
     * @return string
     */
    public function render()
    {
        if (!$this->template) {
            $this->mode('select');
        }

        return parent::render();
    }

    /**
     * Switch template for this query. Determines what would be done
     * on execute.
     *
     * By default it is in SELECT mode
     *
     * @param string $mode
     *
     * @return $this
     */
    public function mode($mode)
    {
        $prop = 'template_'.$mode;

        if (isset($this->{$prop})) {
            $this->mode = $mode;
            $this->template = $this->{$prop};
        } else {
            throw new Exception([
                'Query does not have this mode',
                'mode' => $mode,
            ]);
        }

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
        $q = new static($properties);
        $q->connection = $this->connection;

        return $q;
    }

    /**
     * Returns new Query object of [or] expression.
     *
     * @return Query
     */
    public function orExpr()
    {
        return $this->dsql(['template' => '[orwhere]']);
    }

    /**
     * Returns new Query object of [and] expression.
     *
     * @return Query
     */
    public function andExpr()
    {
        return $this->dsql(['template' => '[andwhere]']);
    }

    /**
     * Returns Query object of [case] expression.
     *
     * @param mixed $operand Optional operand for case expression.
     *
     * @return Query
     */
    public function caseExpr($operand = null)
    {
        $q = $this->dsql(['template' => '[case]']);

        if ($operand !== null) {
            $q->args['case_operand'] = $operand;
        }

        return $q;
    }

    /**
     * Add when/then condition for [case] expression.
     *
     * @param mixed $when Condition as array for normal form [case] statement or just value in case of short form [case] statement
     * @param mixed $then Then expression or value
     *
     * @return $this
     */
    public function when($when, $then)
    {
        $this->args['case_when'][] = [$when, $then];

        return $this;
    }

    /**
     * Add else condition for [case] expression.
     *
     * @param mixed $else Else expression or value
     *
     * @return $this
     */
    //public function else($else) // PHP 5.6 restricts to use such method name. PHP 7 is fine with it
    public function otherwise($else)
    {
        $this->args['case_else'] = $else;

        return $this;
    }

    /**
     * Renders [case].
     *
     * @return string rendered SQL chunk
     */
    protected function _render_case()
    {
        if (!isset($this->args['case_when'])) {
            return;
        }

        $ret = '';

        // operand
        if ($short_form = isset($this->args['case_operand'])) {
            $ret .= ' '.$this->_consume($this->args['case_operand'], 'soft-escape');
        }

        // when, then
        foreach ($this->args['case_when'] as $row) {
            if (!array_key_exists(0, $row) || !array_key_exists(1, $row)) {
                throw new Exception([
                    'Incorrect use of "when" method parameters',
                    'row'  => $row,
                ]);
            }

            $ret .= ' when ';
            if ($short_form) {
                // short-form
                if (is_array($row[0])) {
                    throw new Exception([
                        'When using short form CASE statement, then you should not set array as when() method 1st parameter',
                        'when'  => $row[0],
                    ]);
                }
                $ret .= $this->_consume($row[0], 'param');
            } else {
                $ret .= $this->__render_condition($row[0]);
            }

            // then
            $ret .= ' then '.$this->_consume($row[1], 'param');
        }

        // else
        if (array_key_exists('case_else', $this->args)) {
            $ret .= ' else '.$this->_consume($this->args['case_else'], 'param');
        }

        return ' case'.$ret.' end';
    }

    /**
     * Sets value in args array. Doesn't allow duplicate aliases.
     *
     * @param string $what  Where to set it - table|field
     * @param string $alias Alias name
     * @param mixed  $value Value to set in args array
     */
    protected function _set_args($what, $alias, $value)
    {
        // save value in args
        if ($alias === null) {
            $this->args[$what][] = $value;
        } else {

            // don't allow multiple values with same alias
            if (isset($this->args[$what][$alias])) {
                throw new Exception([
                    'Alias should be unique',
                    'what'  => $what,
                    'alias' => $alias,
                ]);
            }

            $this->args[$what][$alias] = $value;
        }
    }

    /// }}}
}
