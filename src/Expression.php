<?php

declare(strict_types=1);

namespace atk4\dsql;

/**
 * Creates new expression. Optionally specify a string - a piece
 * of SQL code that will become expression template and arguments.
 */
class Expression implements \ArrayAccess, \IteratorAggregate
{
    /**
     * Template string.
     *
     * @var string
     */
    protected $template;

    /**
     * Hash containing configuration accumulated by calling methods
     * such as Query::field(), Query::table(), etc.
     *
     * $args['custom'] is used to store hash of custom template replacements.
     *
     * This property is made public to ease customization and make it accessible
     * from Connection class for example.
     *
     * @var array
     */
    public $args = ['custom' => []];

    /**
     * As per PDO, _param() will convert value into :a, :b, :c .. :aa .. etc.
     *
     * @var string
     */
    protected $paramBase = 'a';

    /**
     * Field, table and alias name escaping symbol.
     * By SQL Standard it's double quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $escape_char = '"';

    /**
     * Used for Linking.
     *
     * @var string
     */
    public $_paramBase;

    /**
     * Will be populated with actual values by _param().
     *
     * @var array
     */
    public $params = [];

    /**
     * When you are willing to execute the query, connection needs to be specified.
     * By default this is PDO object.
     *
     * @var \PDO|Connection
     */
    public $connection;

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
            throw (new Exception('Incorrect use of Expression constructor'))
                ->addMoreInfo('properties', $properties)
                ->addMoreInfo('arguments', $arguments);
        }

        // supports passing template as property value without key 'template'
        if (isset($properties[0])) {
            $properties['template'] = $properties[0];
            unset($properties[0]);
        }

        // save arguments
        if ($arguments !== null) {
            if (!is_array($arguments)) {
                throw (new Exception('Expression arguments must be an array'))
                    ->addMoreInfo('properties', $properties)
                    ->addMoreInfo('arguments', $arguments);
            }
            $this->args['custom'] = $arguments;
        }

        // deal with remaining properties
        foreach ($properties as $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * Casting to string will execute expression and return getOne() value.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getOne();
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
        if ($offset === null) {
            $this->args['custom'][] = $value;
        } else {
            $this->args['custom'][$offset] = $value;
        }
    }

    /**
     * Whether or not an offset exists.
     *
     * @param string An offset to check for
     *
     * @return bool
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->args['custom']);
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
     *
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset)
    {
        return $this->args['custom'][$offset];
    }

    /**
     * Use this instead of "new Expression()" if you want to automatically bind
     * new expression to the same connection as the parent.
     *
     * @param array|string $properties
     * @param array        $arguments
     *
     * @return Expression
     */
    public function expr($properties = [], $arguments = null)
    {
        // If we use DSQL Connection, then we should call expr() from there.
        // Connection->expr() will return correct, connection specific Expression class.
        if ($this->connection instanceof Connection) {
            return $this->connection->expr($properties, $arguments);
        }

        // Otherwise, connection is probably PDO and we don't know which Expression
        // class to use, so we make a smart guess :)
        if ($this instanceof Query) {
            $e = new self($properties, $arguments);
        } else {
            $e = new static($properties, $arguments);
        }

        $e->escape_char = $this->escape_char;
        $e->connection = $this->connection;

        return $e;
    }

    /**
     * Resets arguments.
     *
     * @param string $tag
     *
     * @return $this
     */
    public function reset($tag = null)
    {
        // unset all arguments
        if ($tag === null) {
            $this->args = ['custom' => []];

            return $this;
        }

        if (!is_string($tag)) {
            throw (new Exception('Tag should be string'))
                ->addMoreInfo('tag', $tag);
        }

        // unset custom/argument or argument if such exists
        if ($this->offsetExists($tag)) {
            $this->offsetUnset($tag);
        } elseif (isset($this->args[$tag])) {
            unset($this->args[$tag]);
        }

        return $this;
    }

    /**
     * Recursively renders sub-query or expression, combining parameters.
     *
     * @param mixed  $sql_code    Expression
     * @param string $escape_mode Fall-back escaping mode - param|escape|none
     *
     * @return string|array Quoted expression or array of param names
     */
    protected function _consume($sql_code, $escape_mode = 'param')
    {
        if (!is_object($sql_code)) {
            switch ($escape_mode) {
                case 'param':
                    return $this->_param($sql_code);
                case 'escape':
                    return $this->_escape($sql_code);
                case 'soft-escape':
                    return $this->_escapeSoft($sql_code);
                case 'none':
                    return $sql_code;
            }

            throw (new Exception('$escape_mode value is incorrect'))
                ->addMoreInfo('escape_mode', $escape_mode);
        }

        // User may add Expressionable trait to any class, then pass it's objects
        if ($sql_code instanceof Expressionable) {
            $sql_code = $sql_code->getDSQLExpression($this);
        }

        if (!$sql_code instanceof self) {
            throw (new Exception('Only Expressions or Expressionable objects may be used in Expression'))
                ->addMoreInfo('object', $sql_code);
        }

        // at this point $sql_code is instance of Expression
        $sql_code->params = &$this->params;
        $sql_code->_paramBase = &$this->_paramBase;
        $ret = $sql_code->render();

        // Queries should be wrapped in parentheses in most cases
        if ($sql_code instanceof Query && $sql_code->allowToWrapInParenthesis === true) {
            $ret = '(' . $ret . ')';
        }

        // unset is needed here because ->params=&$othervar->params=foo will also change $othervar.
        // if we unset() first, we’re safe.
        unset($sql_code->{'params'});
        $sql_code->params = [];

        return $ret;
    }

    /**
     * Given the string parameter, it will detect some "deal-breaker" for our
     * soft escaping, such as "*" or "(".
     * Those will typically indicate that expression is passed and shouldn't
     * be escaped.
     */
    protected function isUnescapablePattern($value)
    {
        return is_object($value)
            || $value === '*'
            || strpos($value, '(') !== false
            || strpos($value, $this->escape_char) !== false;
    }

    /**
     * Soft-escaping SQL identifier. This method will attempt to put
     * escaping char around the identifier, however will not do so if you
     * are using special characters like ".", "(" or escaping char.
     *
     * It will smartly escape table.field type of strings resulting
     * in "table"."field".
     *
     * @param mixed $value Any string or array of strings
     *
     * @return string|array Escaped string or array of strings
     */
    protected function _escapeSoft($value)
    {
        // supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        // in some cases we should not escape
        if ($this->isUnescapablePattern($value)) {
            return $value;
        }

        if (is_string($value) && strpos($value, '.') !== false) {
            return implode('.', array_map(__METHOD__, explode('.', $value)));
        }

        return $this->escape_char . trim($value) . $this->escape_char;
    }

    /**
     * Creates new expression where $sql_code appears escaped. Use this
     * method as a conventional means of specifying arguments when you
     * think they might have a nasty back-ticks or commas in the field
     * names.
     *
     * @param string $value
     *
     * @return Expression
     */
    public function escape($value)
    {
        return $this->expr('{}', [$value]);
    }

    /**
     * Escapes argument by adding backticks around it.
     * This will allow you to use reserved SQL words as table or field
     * names such as "table" as well as other characters that SQL
     * permits in the identifiers (e.g. spaces or equation signs).
     *
     * @param mixed $value Any string or array of strings
     *
     * @return string|array Escaped string or array of strings
     */
    protected function _escape($value)
    {
        // supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        // in all other cases we should escape
        $c = $this->escape_char;

        return $c . str_replace($c, $c . $c, $value) . $c;
    }

    /**
     * Converts value into parameter and returns reference. Use only during
     * query rendering. Consider using `_consume()` instead, which will
     * also handle nested expressions properly.
     *
     * @param string|array $value String literal or array of strings containing input data
     *
     * @return string|array Name of parameter or array of names
     */
    protected function _param($value)
    {
        // supports array
        if (is_array($value)) {
            return array_map(__METHOD__, $value);
        }

        $name = ':' . $this->_paramBase;
        ++$this->_paramBase;
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

        if ($this->template === null) {
            throw new Exception('Template is not defined for Expression');
        }

        $res = preg_replace_callback(
            //     param     |  escape   |  escapeSoft
            '/\[[a-z0-9_]*\]|{[a-z0-9_]*}|{{[a-z0-9_]*}}/i',
            function ($matches) use (&$nameless_count) {
                $identifier = substr($matches[0], 1, -1);

                if ($matches[0][0] === '[') {
                    $escaping = 'param';
                } elseif ($matches[0][0] === '{') {
                    if ($matches[0][1] === '{') {
                        $escaping = 'soft-escape';
                        $identifier = substr($identifier, 1, -1);
                    } else {
                        $escaping = 'escape';
                    }
                }

                // Allow template to contain []
                if ($identifier === '') {
                    $identifier = $nameless_count++;

                    // use rendering only with named tags
                }
                $fx = '_render_' . $identifier;

                // [foo] will attempt to call $this->_render_foo()

                if (array_key_exists($identifier, $this->args['custom'])) {
                    $value = $this->_consume($this->args['custom'][$identifier], $escaping);
                } elseif (method_exists($this, $fx)) {
                    $value = $this->{$fx}();
                } else {
                    throw (new Exception('Expression could not render tag'))
                        ->addMoreInfo('tag', $identifier);
                }

                return is_array($value) ? '(' . implode(',', $value) . ')' : $value;
            },
            $this->template
        );
        unset($this->{'_paramBase'});

        return trim($res);
    }

    /**
     * Return formatted debug output.
     *
     * Ignore false positive warnings of PHPMD.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return string SQL syntax of query
     */
    public function getDebugQuery()
    {
        $result = $this->render();

        foreach (array_reverse($this->params) as $key => $val) {
            if (is_numeric($val)) {
                $replacement = $val . '\1';
            } elseif (is_string($val)) {
                $replacement = "'" . addslashes($val) . "'\\1";
            } elseif ($val === null) {
                $replacement = 'NULL\1';
            } else {
                $replacement = $val . '\\1';
            }

            $result = preg_replace('/' . $key . '([^_]|$)/', $replacement, $result);
        }

        if (func_num_args() > 0) { // remove in 2020-dec
            throw new Exception('Use of $html argument and html rendering has been deprecated');
        }

        return str_replace('#lte#', '<=', strip_tags(str_replace('<=', '#lte#', $result), '<>'));
    }

    public function __debugInfo()
    {
        $arr = [
            'R' => false,
            'template' => $this->template,
            'params' => $this->params,
            //            'connection' => $this->connection,
            'args' => $this->args,
        ];

        try {
            $arr['R'] = $this->getDebugQuery();
        } catch (\Exception $e) {
            $arr['R'] = $e->getMessage();
        }

        return $arr;
    }

    /**
     * Execute expression.
     *
     * @param \PDO|Connection $connection
     *
     * @return \PDOStatement
     */
    public function execute(object $connection = null)
    {
        if ($connection === null) {
            $connection = $this->connection;
        }

        // If it's a PDO connection, we're cool
        if ($connection instanceof \PDO) {
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $query = $this->render();

            try {
                $statement = $connection->prepare($query);

                // workaround to support LOB data type 1/2, see https://github.com/doctrine/dbal/pull/2434
                $statement->boundValues = [];

                foreach ($this->params as $key => $val) {
                    if (is_int($val)) {
                        $type = \PDO::PARAM_INT;
                    } elseif (is_bool($val)) {
                        // SQL does not like booleans at all, so convert them INT
                        $type = \PDO::PARAM_INT;
                        $val = (int) $val;
                    } elseif ($val === null) {
                        $type = \PDO::PARAM_NULL;
                    } elseif (is_string($val) || is_float($val)) {
                        $type = \PDO::PARAM_STR;
                    } elseif (is_resource($val)) {
                        $type = \PDO::PARAM_LOB;
                    } else {
                        throw (new Exception('Incorrect param type'))
                            ->addMoreInfo('key', $key)
                            ->addMoreInfo('value', $val)
                            ->addMoreInfo('type', gettype($val));
                    }

                    // workaround to support LOB data type 2/2, see https://github.com/doctrine/dbal/pull/2434
                    $statement->boundValues[$key] = $val;
                    if ($type === \PDO::PARAM_STR) {
                        $bind = $statement->bindParam($key, $statement->boundValues[$key], $type, strlen((string) $val));
                    } else {
                        $bind = $statement->bindParam($key, $statement->boundValues[$key], $type);
                    }

                    if (!$bind) {
                        throw (new Exception('Unable to bind parameter'))
                            ->addMoreInfo('param', $key)
                            ->addMoreInfo('value', $val)
                            ->addMoreInfo('type', $type);
                    }
                }

                $statement->setFetchMode(\PDO::FETCH_ASSOC);
                $statement->execute();
            } catch (\PDOException $e) {
                $new = (new ExecuteException('DSQL got Exception when executing this query', $e->errorInfo[1]))
                    ->addMoreInfo('error', $e->errorInfo[2])
                    ->addMoreInfo('query', $this->getDebugQuery());

                throw $new;
            }

            return $statement;
        }

        return $connection->execute($this);
    }

    /**
     * Returns ArrayIterator, for example PDOStatement.
     *
     * @return \PDOStatement
     * @abstracting \IteratorAggregate
     */
    public function getIterator()
    {
        return $this->execute();
    }

    // {{{ Result Querying

    /**
     * @param string|int|bool|null $v
     */
    private function getCastValue($v): ?string
    {
        if ($v === null) {
            return null;
        } elseif (is_bool($v)) {
            return $v ? '1' : '0';
        }

        return (string) $v;
    }

    /**
     * Executes expression and return whole result-set in form of array of hashes.
     *
     * @return string[][]|null[][]
     */
    public function get(): array
    {
        $stmt = $this->execute();

        if ($stmt instanceof \Generator) {
            $res = iterator_to_array($stmt);
        } else {
            $res = $stmt->fetchAll();
        }

        return array_map(function ($row) {
            return array_map(function ($v) {
                return $this->getCastValue($v);
            }, $row);
        }, $res);
    }

    /**
     * Executes expression and returns first row of data from result-set as a hash.
     *
     * @return string[]|null[]|null
     */
    public function getRow(): ?array
    {
        $stmt = $this->execute();

        if ($stmt instanceof \Generator) {
            $res = $stmt->current();
        } else {
            $res = $stmt->fetch();
            if ($res === false) {
                $res = null;
            }
        }

        if ($res === null) {
            return null;
        }

        return array_map(function ($v) {
            return $this->getCastValue($v);
        }, $res);
    }

    /**
     * Executes expression and return first value of first row of data from result-set.
     */
    public function getOne(): ?string
    {
        $row = $this->getRow();
        if ($row === null || count($row) === 0) {
            throw (new Exception('Unable to fetch single cell of data for getOne from this query'))
                ->addMoreInfo('result', $row)
                ->addMoreInfo('query', $this->getDebugQuery());
        }

        return reset($row);
    }

    // }}}
}
