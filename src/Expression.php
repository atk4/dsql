<?php // vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Creates new expression. Optionally specify a string - a piece
 * of SQL code that will become expression template and arguments.
 *
 * See below for call patterns
 */
class Expression implements \ArrayAccess, \IteratorAggregate
{
    protected $template = null;

    /**
     * Backticks are added around all fields. Set this to blank string to avoid.
     *
     * @var string
     */
    protected $escapeChar = '`';

    /**
     * As per PDO, _param() will convert value into :a, :b, :c .. :aa .. etc.
     *
     * @var string
     */
    protected $paramBase = ':a';

    /**
     * Used for Linking
     *
     * @var string
     */
    public $_paramBase = null;

    /**
     * Will be populated with actual values by _param()
     *
     * @var array
     */
    public $params = [];

    /**
     * When you are willing to execute the query, connection needs to be specified
     */
    public $connection = null;

    /**
     * Specifying options to constructors will override default
     * attribute values of this class
     *
     * @param string|array $template
     * @param array        $arguments
     */
    public function __construct($template = [], $arguments = null)
    {
        if (is_string($template)) {
            $options = ['template' => $template];
        } elseif (is_array($template)) {
            $options = $template;
        } else {
            throw new Exception('$template must be a string in Expression::__construct()');
        }

        // new Expression('unix_timestamp([])', [$date]);
        if ($arguments) {
            if (!is_array($arguments)) {
                throw new Exception('$arguments must be an array in Expression::__construct()');
            }
            $this->args['custom'] = $arguments;
        }

        // Deal with remaining options
        foreach ($options as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * ???
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->args['custom'][] = $value;
        } else {
            $this->args['custom'][$offset] = $value;
        }
    }

    /**
     * ???
     */
    public function offsetExists($offset)
    {
        return isset($this->args['custom'][$offset]);
    }

    /**
     * ???
     */
    public function offsetUnset($offset)
    {
        unset($this->args['custom'][$offset]);
    }

    /**
     * ???
     */
    public function offsetGet($offset)
    {
        return isset($this->args['custom'][$offset]) ? $this->args['custom'][$offset] : null;
    }

    /**
     * Use this instead of "new Expression()" if you want to automatically bind
     * expression to the same connection as the parent.
     */
    public function expr($expr, $options = [])
    {
        $options['connection'] = $this->connection;
        $class = get_class($this);
        return new Expression($expr, $options);
    }

    /**
     * Use this instead of "new Query()" if you want to automatically bind
     * expression to the same connection as the parent.
     */
    public function dsql($options = [])
    {
        $options['connection'] = $this->connection;
        return new Query($options);
    }

    /**
     * Recursively renders sub-query or expression, combining parameters.
     * If the argument is more likely to be a field, use tick=true.
     *
     * @param string|array|object $sql_code    Expression
     * @param string              $escape_mode Fall-back escaping mode - param|escape|none
     *
     * @return string Quoted expression
     */
    protected function _consume($sql_code, $escape_mode = 'param')
    {
        if (!is_object($sql_code)) {
            switch ($escape_mode) {
                case 'param':
                    return $this->_param($sql_code);
                case 'escape':
                    return $this->_escape($sql_code);
                case 'none':
                    return $sql_code;
            }
        }

        // User may add Expressionable trait to any class, then pass it's objects
        if ($sql_code instanceof Expressionable) {
            $sql_code = $sql_code->getDSQLExpression();
        }

        if (!$sql_code instanceof Expression) {
            throw new Exception('Foreign objects may not be passed into DSQL');
        }

         //|| !$sql_code instanceof Expression) {
        $sql_code->params = &$this->params;
        $sql_code->_paramBase = &$this->_paramBase;
        $ret = $sql_code->render();

        // Queries should be wrapped in most cases
        if ($sql_code instanceof Query) {
            $ret = '(' . $ret . ')';
        }
        unset($sql_code->params);
        $sql_code->params = [];

        return $ret;
    }

    /**
     * Escapes argument by adding backticks around it.
     * This will allow you to use reserved SQL words as table or field
     * names such as "table".
     *
     * @param string|array $sql_code Any string or array of strings
     *
     * @return string|array Escaped string or array of strings
     */
    protected function _escape($sql_code)
    {
        // Supports array
        if (is_array($sql_code)) {
            return array_map([$this, '_escape'], $sql_code);
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
     * Converts value into parameter and returns reference. Use only during
     * query rendering. Consider using `_consume()` instead, which will
     * also handle nested expressions properly.
     *
     * @param string|array $value String literal containing input data
     *
     * @return string|array Safe and escaped string
     */
    protected function _param($value)
    {
        if (is_array($value)) {
            return array_map([$this, '_param'], $value);
        }

        $name = $this->_paramBase;
        $this->_paramBase++;
        $this->params[$name] = $value;

        return $name;
    }

    /**
     * ???
     *
     * @return string Rendered query
     */
    public function render()
    {
        $nameless_count = 0;
        if (!isset($this->_paramBase)) {
            $this->_paramBase = $this->paramBase;
        }

        $res= preg_replace_callback(
            '/\[([a-z0-9_]*)\]/',
            function ($matches) use (&$nameless_count) {

                // Allow template to contain []
                $identifier = $matches[1];
                if ($identifier === "") {
                    $identifier = $nameless_count++;
                }

                // [foo] will attempt to call $this->_render_foo()
                $fx = '_render_'.$matches[1];

                if (isset($this->args['custom'][$identifier])) {
                    return $this->_consume($this->args['custom'][$identifier]);
                } elseif (method_exists($this, $fx)) {
                    return $this->$fx();
                } else {
                    throw new Exception('Expression could not render ['.$identifier.']');
                }
            },
            $this->template
        );
        unset($this->_paramBase);

        return trim($res);
    }

    /**
     * Return formatted debug output.
     *
     * @return string SQL syntax of query
     */
    public function getDebugQuery()
    {
        $d = $this->render();

        $pp = array();
        $d = preg_replace('/`([^`]*)`/', '`<font color="black">\1</font>`', $d);
        foreach (array_reverse($this->params) as $key => $val) {
            if (is_string($val)) {
                $d = preg_replace('/'.$key.'([^_]|$)/', '"<font color="green">'.
                    htmlspecialchars(addslashes($val)).'</font>"\1', $d);
            } elseif (is_null($val)) {
                $d = preg_replace(
                    '/'.$key.'([^_]|$)/',
                    '<font color="black">NULL</font>\1',
                    $d
                );
            } elseif (is_numeric($val)) {
                $d = preg_replace(
                    '/'.$key.'([^_]|$)/',
                    '<font color="red">'.$val.'</font>\1',
                    $d
                );
            } else {
                $d = preg_replace('/'.$key.'([^_]|$)/', $val.'\1', $d);
            }

            $pp[] = $key;
        }

        return $d." <font color='gray'>[" . implode(', ', $pp) . ']</font>';
    }

    public function execute($connection = null)
    {
        if ($connection == null) {
            $connection = $this->connection;
        }

        // If it's a PDO connection, we're cool
        if ($connection instanceof \PDO) {
            // We support PDO
            $query = $this->render();
            $statement = $connection->prepare($query);
            foreach ($this->params as $key => $val) {

                if (is_int($val)) {
                    $type = \PDO::PARAM_INT;
                } elseif (is_bool($val)) {
                    $type = \PDO::PARAM_BOOL;
                } elseif (is_null($val)) {
                    $type = \PDO::PARAM_NULL;
                } elseif (is_string($val)) {
                    $type = \PDO::PARAM_STR;
                } else {
                    throw new Exception('Incorrect param type');
                }

                if (!$statement->bindValue($key, $val, $type)) {
                    throw new Exception('Unable to bind parameter');
                }
            }

            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute();
            return $statement;
            
        } else {
            return $connection->execute($this);
        }
    }

    public function getIterator()
    {
        return $this->execute();
    }

    // {{{ Result Querying
    public function get()
    {
        return $this->execute()->fetchAll();
    }

    public function getOne()
    {
        $data = $this->getRow();
        $one = array_shift($data);
        return $one;
    }

    public function getRow()
    {
        return $this->execute()->fetch();
    }
    // }}}
}
