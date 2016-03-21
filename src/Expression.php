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
    /**
     * Template string.
     *
     * @var string
     */
    protected $template = null;

    /**
     * Hash containing configuration accumulated by calling methods
     * such as Query::field(), Query::table(), etc.
     *
     * $args['custom'] is used to store hash of custom template replacements.
     *
     * @var array
     */
    protected $args = [];

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
     * When you are willing to execute the query, connection needs to be specified.
     * By default this is PDO object.
     *
     * @var PDO
     */
    public $connection = null;

    /**
     * Specifying options to constructors will override default
     * attribute values of this class.
     *
     * If $properties is passed as string, then it's treated as template.
     *
     * @param string|array $properties
     * @param array        $arguments
     */
    public function __construct($properties = [], $arguments = null)
    {
        // save template
        if (is_string($properties)) {
            $properties = ['template' => $properties];
        } elseif (!is_array($properties)) {
            throw new Exception('1st parameter must be a string or array in '.__METHOD__);
        }
        
        // supports passing template as property value without key 'template'
        if (isset($properties[0])) {
            $properties['template'] = $properties[0];
            unset($properties[0]);
        }

        // save arguments
        if ($arguments !== null) {
            if (!is_array($arguments)) {
                throw new Exception('2nd parameter must be an array in '.__METHOD__);
            }
            $this->args['custom'] = $arguments;
        }

        // deal with remaining properties
        foreach ($properties as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param string The offset to assign the value to
     * @param mixed  The value to set
     * @abstracting ArrayAccess
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
     * Whether or not an offset exists.
     *
     * @param string An offset to check for
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->args['custom'][$offset]);
    }

    /**
     * Unsets an offset.
     *
     * @param string The offset to unset
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset)
    {
        unset($this->args['custom'][$offset]);
    }

    /**
     * Returns the value at specified offset.
     *
     * @param string The offset to retrieve
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset)
    {
        return isset($this->args['custom'][$offset]) ? $this->args['custom'][$offset] : null;
    }

    /**
     * Use this instead of "new Expression()" if you want to automatically bind
     * new expression to the same connection as the parent.
     *
     * @param array|string $properties
     * @param array $arguments
     *
     * @return Expression
     */
    public function expr($properties = [], $arguments = null)
    {
        $e = new Expression($properties, $arguments);
        $e->connection = $this->connection;

        return $e;
    }

    /**
     * Recursively renders sub-query or expression, combining parameters.
     * If the argument is more likely to be a field, use tick=true.
     *
     * @param mixed   $sql_code    Expression
     * @param string  $escape_mode Fall-back escaping mode - param|escape|none
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
            throw new Exception('Foreign objects may not be passed into DSQL in '.__METHOD__);
        }

         //|| !$sql_code instanceof Expression) {
        $sql_code->params = &$this->params;
        $sql_code->_paramBase = &$this->_paramBase;
        $ret = $sql_code->render();

        // Queries should be wrapped in parentheses in most cases
        if ($sql_code instanceof Query) {
            $ret = '(' . $ret . ')';
        }
        
        /* useless ? */unset($sql_code->params);
        $sql_code->params = [];

        return $ret;
    }

    /**
     * Escapes argument by adding backticks around it.
     * This will allow you to use reserved SQL words as table or field
     * names such as "table".
     *
     * @param mixed $value Any string or array of strings
     *
     * @return string|array Escaped string or array of strings
     */
    protected function _escape($value)
    {
        // Supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        // in some cases we should not escape
        if (!$this->escapeChar
            || is_object($value)
            || is_numeric($value)
            || $value === '*'
            || strpos($value, '.') !== false
            || strpos($value, '(') !== false
            || strpos($value, $this->escapeChar) !== false
        ) {
            return $value;
        }

        // in all other cases we should escape
        return $this->escapeChar . $value . $this->escapeChar;
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
            return array_map(__METHOD__, $value);
        }

        $name = $this->_paramBase;
        $this->_paramBase++;
        $this->params[$name] = $value;

        return $name;
    }

    /**
     * Render expression and return it as string.
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
                    throw new Exception('Expression could not render ['.$identifier.'] in '.__METHOD__);
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
        $d = preg_replace('/`([^`]*)`/', '`<span style="color:black">\1</span>`', $d);
        foreach (array_reverse($this->params) as $key => $val) {
            if (is_string($val)) {
                $d = preg_replace('/'.$key.'([^_]|$)/', '"<span style="color:green">'.
                    htmlspecialchars(addslashes($val)).'</span>"\1', $d);
            } elseif (is_null($val)) {
                $d = preg_replace(
                    '/'.$key.'([^_]|$)/',
                    '<span style="color:black">NULL</span>\1',
                    $d
                );
            } elseif (is_numeric($val)) {
                $d = preg_replace(
                    '/'.$key.'([^_]|$)/',
                    '<span style="color:red">'.$val.'</span>\1',
                    $d
                );
            } else {
                $d = preg_replace('/'.$key.'([^_]|$)/', $val.'\1', $d);
            }

            $pp[] = $key;
        }

        return $d.' <span style="color:gray">[' . implode(', ', $pp) . ']</span>';
    }

    /**
     * Execute expression
     *
     * @param PDO $connection
     *
     * @return PDOStatement
     */
    public function execute($connection = null)
    {
        if ($connection === null) {
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
                    throw new Exception('Incorrect param type in '.__METHOD__);
                }

                if (!$statement->bindValue($key, $val, $type)) {
                    throw new Exception('Unable to bind parameter in '.__METHOD__);
                }
            }

            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute();
            return $statement;
            
        } else {
            return $connection->execute($this);
        }
    }

    /**
     * Returns ArrayIterator, for example PDOStatement
     *
     * @return PDOStatement
     * @abstracting IteratorAggregate
     */
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
