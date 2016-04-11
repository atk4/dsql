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
        'select'   => 'select [field] [from] [table][join][where][having][order][limit]',
        'delete'   => 'delete [from] [table][where][having]',
        'insert'   => 'insert into [table_noalias] ([set_fields]) values ([set_values])',
        'replace'  => 'replace into [table_noalias] ([set_fields]) values ([set_values])',
        'update'   => 'update [table_noalias] set [set] [where]',
        'truncate' => 'truncate table [table_noalias]',
    ];

    /**
     * Query will use one of the predefined "templates". The mode will contain
     * name of template used. Basically it's array key of $templates property.
     *
     * @var string
     */
    public $mode = null;

    /**
     * If no fields are defined, this field is used.
     *
     * @var string|Expression
     */
    public $defaultField = '*';

    /**
     * Name of base table to use when using default join()
     * This is set by table(). If you are using multiple
     * tables or selecting from expression, then $main_table
     * is set to false as it is irrelevant.
     *
     * @var null|false|string
     */
    protected $main_table = null;


    // {{{ Field specification and rendering
    /**
     * Adds new column to resulting select by querying $field.
     *
     * @todo I want to get rid of $table argument, because it's either
     *  can embedded into a field directly or not necessary for expression.
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
        } elseif (is_object($field) && $alias === null) {
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
        $ret = [];

        // If no fields were defined, use defaultField
        if (!isset($this->args['fields']) || empty($this->args['fields'])) {
            if ($this->defaultField instanceof Expression) {
                return $this->_consume($this->defaultField);
            }
            return (string)$this->defaultField;
        }

        // process each defined field
        foreach ($this->args['fields'] as $row) {
            list($field, $table, $alias) = $row;

            // Do not use alias, if it's same as field
            if ($alias === $field) {
                $alias = null;
            }

            // Will parameterize the value and backtick if necessary
            $field = $this->_consume($field, 'escape');

            /* TODO: Commented until I figure out what this does
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

            $ret[] = $field;
        }

        return implode(',', $ret);
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
        // comma-separated table names
        if (is_string($table) && strpos($table, ',') !== false) {
            $table = explode(',', $table);
        }

        // array of tables - recursively process each
        if (is_array($table)) {

            if ($alias !== null) {
                throw new Exception('You cannot use single alias with multiple tables');
            }

            foreach ($table as $alias => $t) {
                if (is_numeric($alias)) {
                    $alias = null;
                }
                $this->table($t, $alias);
            }

            return $this;
        }

        // table can be expression, but then we can only set the table once
        // @todo WHY such restriction ?
        if ($table instanceof Expression) {

            if (isset($this->args['table'])) {
                throw new Exception('You cannot use table(expression). table() was called before');
            }

            $this->main_table = false;
            // @todo Imants: Only saves table (expression) and doesn't save table/expression alias!?!
            $this->args['table'] = $table;

            return $this;
        }

        // initialize args[table] array
        if (!isset($this->args['table'])) {
            $this->args['table'] = array();
        }

        // @todo WHY such restriction ?
        if ($this->args['table'] instanceof Expression) {
            throw new Exception('You cannot use table(). You have already used table(expression) previously.');
        }

        // trim table name just in case developer called it like 'employees,    jobs'
        $table = trim($table);

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
        // will be joined for output
        $ret = [];

        if (!isset($this->args['table'])) {
            return '';
        }

        if ($this->args['table'] instanceof Expression) {

            // This will wrap into () if Query object is used here
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
        // will be joined for output
        $ret = [];

        if ($this->args['table'] instanceof Expression) {
            throw new Exception('Table cannot be expression for UPDATE / INSERT queries in '.__METHOD__);
        }

        foreach ($this->args['table'] as $row) {
            list($table, ) = $row;

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
        /**
         * @todo Imants: maybe we can change this to
         *  return !empty($this->args['table']) ? 'from' : ''
         * and get rid of main_table.
         */
    }
    /// }}}

    // {{{ join()
    /**
     * Joins your query with another table. Join will use $main_table in
     * to reference the main table, unless you specify it explicitly
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
     * @param string $foreign_table  Table to join with
     * @param mixed  $master_field   Field in master table
     * @param string $join_kind      'left' or 'inner', etc
     * @param string $_foreign_alias Internal, don't use
     *
     * @return DB_dsql $this
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
        $j = array();

        if (is_null($_foreign_alias)) {
            @list($foreign_table, $_foreign_alias) = explode(' ', $foreign_table, 2);
        }

        // Split and deduce fields
        @list($f1, $f2) = explode('.', $foreign_table, 2);

        if (is_object($master_field)) {
            $j['expr'] = $master_field;
        } else {
            // Split and deduce primary table
            if (is_null($master_field)) {
                list($m1, $m2) = array(null, null);
            } else {
                list($m1, $m2) = explode('.', $master_field, 2);
            }
            if (is_null($m2)) {
                $m2 = $m1;
                $m1 = null;
            }
            if (is_null($m1)) {
                $m1 = $this->main_table;
            }

            // Identify fields we use for joins
            if (is_null($f2) && is_null($m2)) {
                $m2 = $f1.'_id';
            }
            if (is_null($m2)) {
                $m2 = 'id';
            }
            $j['m1'] = $m1;
            $j['m2'] = $m2;
        }
        $j['f1'] = $f1;
        if (is_null($f2)) {
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
            return'';
        }
        $joins = array();
        foreach ($this->args['join'] as $j) {
            $jj = '';

            $jj .= $j['t'].' join ';

            $jj .= $this->_escape($j['f1']);

            if (!is_null($j['fa'])) {
                $jj .= ' as '.$this->_escape($j['fa']);
            }

            $jj .= ' on ';

            if (isset($j['expr'])) {
                $jj .= $this->_consume($j['expr']);
            } else {
                $jj .=
                    $this->_escape($j['fa'] ?: $j['f1']).'.'.
                    $this->_escape($j['f2']).' = '.
                    (is_null($j['m1'])?'':$this->_escape($j['m1']).'.').
                    $this->_escape($j['m2']);
            }
            $joins[] = $jj;
        }

        return implode(' ', $joins);
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
     * @param mixed  $cond      Condition such as '=', '>' or 'is not'
     * @param mixed  $value     Value. Will be quoted unless you pass expression
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
        // will be joined for output
        $ret = [];

        // where() might have been called multiple times. Collect all conditions,
        // then join them with AND keyword
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
                } elseif ($value instanceof Query && $value->mode === 'select') {
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
                $ret[] = $this->_consume($field, 'escape').' '.$cond.' '.$value;
                continue;
            }

            // if value is object, then it should be Expression or Query itself
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

    // {{{ group()
    /**
     * Implements GROUP BY functionality. Simply pass either field name
     * as string or expression.
     *
     * @param string|object $group Group by this
     *
     * @return DB_dsql $this
     */
    public function group($group)
    {
        // Case with comma-separated fields
        if (is_string($group) && strpos($group, ',') !== false) {
            $group = explode(',', $group);
        }

        if (is_array($group)) {
            foreach($group as $g){
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

        $g = implode(', ', array_map(function($a){return $this->_consume($a, 'escape');}, $this->args['group']));

        return ' group by '.$g;
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

        if (is_string($field)) {
            $this->args['set'][$field] = $value;
        } else {
            throw new Exception('Field name should be string in '.__METHOD__);
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

        if ($this->args['set']) {
            foreach ($this->args['set'] as $field => $value) {
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
            foreach ($this->args['set'] as $field => $value) {
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
            foreach ($this->args['set'] as $field => $value) {
                $value = $this->_consume($value, 'param');

                $ret[] = $value;
            }
        }

        return implode(',', $ret);
    }
    /// }}}

    // {{{ Query Modes
    /**
     * Execute select statement
     *
     * @return PDOStatement
     */
    public function select()
    {
        return $this->selectTemplate('select')->execute();
    }

    /**
     * Execute insert statement
     *
     * @return PDOStatement
     */
    public function insert()
    {
        return $this->selectTemplate('insert')->execute();
    }

    /**
     * Execute update statement
     *
     * @return PDOStatement
     */
    public function update()
    {
        return $this->selectTemplate('update')->execute();
    }

    /**
     * Execute replace statement
     *
     * @return PDOStatement
     */
    public function replace()
    {
        return $this->selectTemplate('replace')->execute();
    }

    /**
     * Execute delete statement
     *
     * @return PDOStatement
     */
    public function delete()
    {
        return $this->selectTemplate('delete')->execute();
    }

    /**
     * Execute truncate statement
     *
     * @return PDOStatement
     */
    public function truncate()
    {
        return $this->selectTemplate('truncate')->execute();
    }
    // }}}

    // {{{ Limit
    /**
     * Limit how many rows will be returned.
     *
     * @param int $cnt   Number of rows to return
     * @param int $shift Offset, how many rows to skip
     *
     * @return DB_dsql $this
     */
    public function limit($cnt, $shift = null)
    {
        $this->args['limit'] = array(
            'cnt' => $cnt,
            'shift' => $shift,
        );

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
     * @param string|bool $desc  true to sort descending
     *
     * @return DB_dsql $this
     */
    public function order($order, $desc = null)
    {
        // Case with comma-separated fields or first argument being an array
        if (is_string($order) && strpos($order, ',') !== false) {
            // Check for multiple
            $order = explode(',', $order);
        }
        if (is_array($order)) {
            if (!is_null($desc)) {
                throw new Exception(
                    'If first argument is array, second argument must not be used'
                );
            }
            foreach (array_reverse($order) as $o) {
                $this->order($o);
            }

            return $this;
        }

        // First argument may contain space, to divide field and keyword
        if (is_null($desc) && is_string($order) && strpos($order, ' ') !== false) {
            list($order, $desc) = array_map('trim', explode(' ', trim($order), 2));
        }

        if (is_string($order) && strpos($order, '.') !== false) {
            $order = implode('.', $this->_escape(explode('.', $order)));
        }

        if (is_bool($desc)) {
            $desc = $desc ? 'desc' : '';
        } elseif (strtolower($desc) === 'asc') {
            $desc = '';
        } elseif ($desc && strtolower($desc) != 'desc') {
            throw new Exception(['Incorrect ordering keyword', 'order by'=>$desc]);
        }

        $this->args['order'][] = array($order, $desc);

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
        $x = array();
        foreach ($this->args['order'] as $tmp) {
            list($arg, $desc) = $tmp;
            $x[] = $this->_consume($arg, 'escape').($desc ? (' '.$desc) : '');
        }

        return ' order by '.implode(', ', array_reverse($x));
    }
    // }}}

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
     * @param string $mode A key for $this->templates
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
        $q = new Query($properties);
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
    /// }}}
}
