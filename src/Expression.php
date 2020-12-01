<?php

declare(strict_types=1);

namespace atk4\dsql;

use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\Exception as DbalException;

class Expression implements \ArrayAccess, \IteratorAggregate
{
    /** @const string "[]" in template, escape as parameter */
    protected const ESCAPE_PARAM = 'param';
    /** @const string "{}" in template, escape as identifier */
    protected const ESCAPE_IDENTIFIER = 'identifier';
    /** @const string "{{}}" in template, escape as identifier, but keep input with special characters like "." or "(" unescaped */
    protected const ESCAPE_IDENTIFIER_SOFT = 'identifier-soft';
    /** @const string keep input as is */
    protected const ESCAPE_NONE = 'none';

    /** @var string */
    protected $template;

    /**
     * Configuration accumulated by calling methods such as Query::field(), Query::table(), etc.
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
     * As per PDO, escapeParam() will convert value into :a, :b, :c .. :aa .. etc.
     *
     * @var string
     */
    protected $paramBase = 'a';

    /**
     * Identifier (table, column, ...) escaping symbol. By SQL Standard it's double
     * quote, but MySQL uses backtick.
     *
     * @var string
     */
    protected $escape_char = '"';

    /** @var string used for linking */
    private $_paramBase;

    /** @var array Populated with actual values by escapeParam() */
    public $params = [];

    /** @var Connection */
    public $connection;

    /** @var bool Wrap the expression in parentheses when consumed by another expression or not. */
    public $wrapInParentheses = false;

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
     * @deprecated will be removed in v2.5
     */
    public function __toString()
    {
        'trigger_error'('Method is deprecated. Use $this->getOne() instead', E_USER_DEPRECATED);

        return $this->getOne();
    }

    /**
     * Whether or not an offset exists.
     *
     * @param string An offset to check for
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->args['custom']);
    }

    /**
     * Returns the value at specified offset.
     *
     * @param string The offset to retrieve
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->args['custom'][$offset];
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param string The offset to assign the value to
     * @param mixed  The value to set
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->args['custom'][] = $value;
        } else {
            $this->args['custom'][$offset] = $value;
        }
    }

    /**
     * Unsets an offset.
     *
     * @param string The offset to unset
     */
    public function offsetUnset($offset): void
    {
        unset($this->args['custom'][$offset]);
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
            // TODO - condition above always satisfied when connection is set - adjust tests,
            // so connection is always set and remove the code below
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
     * @param mixed  $expression Expression
     * @param string $escapeMode Fall-back escaping mode - using one of the Expression::ESCAPE_* constants
     *
     * @return string|array Quoted expression or array of param names
     */
    protected function consume($expression, string $escapeMode = self::ESCAPE_PARAM)
    {
        if (!is_object($expression)) {
            switch ($escapeMode) {
                case self::ESCAPE_PARAM:
                    return $this->escapeParam($expression);
                case self::ESCAPE_IDENTIFIER:
                    return $this->escapeIdentifier($expression);
                case self::ESCAPE_IDENTIFIER_SOFT:
                    return $this->escapeIdentifierSoft($expression);
                case self::ESCAPE_NONE:
                    return $expression;
            }

            throw (new Exception('$escapeMode value is incorrect'))
                ->addMoreInfo('escapeMode', $escapeMode);
        }

        // User may add Expressionable trait to any class, then pass it's objects
        if ($expression instanceof Expressionable) {
            $expression = $expression->getDsqlExpression($this);
        }

        if (!$expression instanceof self) {
            throw (new Exception('Only Expressions or Expressionable objects may be used in Expression'))
                ->addMoreInfo('object', $expression);
        }

        // at this point $sql_code is instance of Expression
        $expression->params = $this->params;
        $expression->_paramBase = $this->_paramBase;
        try {
            $ret = $expression->render();
            $this->params = $expression->params;
            $this->_paramBase = $expression->_paramBase;
        } finally {
            $expression->params = [];
            $expression->_paramBase = null;
        }

        if (isset($expression->allowToWrapInParenthesis)) {
            'trigger_error'('Usage of Query::$allowToWrapInParenthesis is deprecated, use $wrapInParentheses instead - will be removed in version 2.5', E_USER_DEPRECATED);

            $expression->wrapInParentheses = $expression->allowToWrapInParenthesis;
        }

        // Wrap in parentheses if expression requires so
        if ($expression->wrapInParentheses === true) {
            $ret = '(' . $ret . ')';
        }

        return $ret;
    }

    /**
     * Creates new expression where $value appears escaped. Use this
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
     * Converts value into parameter and returns reference. Use only during
     * query rendering. Consider using `consume()` instead, which will
     * also handle nested expressions properly.
     *
     * @param string|int|float $value
     *
     * @return string Name of parameter
     */
    protected function escapeParam($value): string
    {
        $name = ':' . $this->_paramBase;
        ++$this->_paramBase;
        $this->params[$name] = $value;

        return $name;
    }

    /**
     * Escapes argument by adding backticks around it.
     * This will allow you to use reserved SQL words as table or field
     * names such as "table" as well as other characters that SQL
     * permits in the identifiers (e.g. spaces or equation signs).
     */
    protected function escapeIdentifier(string $value): string
    {
        // in all other cases we should escape
        $c = $this->escape_char;

        return $c . str_replace($c, $c . $c, $value) . $c;
    }

    /**
     * Soft-escaping SQL identifier. This method will attempt to put
     * escaping char around the identifier, however will not do so if you
     * are using special characters like ".", "(" or escaping char.
     *
     * It will smartly escape table.field type of strings resulting
     * in "table"."field".
     */
    protected function escapeIdentifierSoft(string $value): string
    {
        // in some cases we should not escape
        if ($this->isUnescapablePattern($value)) {
            return $value;
        }

        if (strpos($value, '.') !== false) {
            return implode('.', array_map(__METHOD__, explode('.', $value)));
        }

        return $this->escape_char . trim($value) . $this->escape_char;
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
     * Render expression and return it as string.
     *
     * @return string Rendered query
     */
    public function render()
    {
        $hadUnderscoreParamBase = isset($this->_paramBase);
        if (!$hadUnderscoreParamBase) {
            $hadUnderscoreParamBase = false;
            $this->_paramBase = $this->paramBase;
        }

        if ($this->template === null) {
            throw new Exception('Template is not defined for Expression');
        }

        $nameless_count = 0;

        // - [xxx] = param
        // - {xxx} = escape
        // - {{xxx}} = escapeSoft
        $res = preg_replace_callback(
            <<<'EOF'
                ~
                 '(?:[^'\\]+|\\.|'')*'\K
                |"(?:[^"\\]+|\\.|"")*"\K
                |`(?:[^`\\]+|\\.|``)*`\K
                |\[\w*\]
                |\{\w*\}
                |\{\{\w*\}\}
                ~xs
                EOF,
            function ($matches) use (&$nameless_count) {
                if ($matches[0] === '') {
                    return '';
                }

                $identifier = substr($matches[0], 1, -1);

                if (substr($matches[0], 0, 1) === '[') {
                    $escaping = self::ESCAPE_PARAM;
                } elseif (substr($matches[0], 0, 1) === '{') {
                    if (substr($matches[0], 1, 1) === '{') {
                        $escaping = self::ESCAPE_IDENTIFIER_SOFT;
                        $identifier = substr($identifier, 1, -1);
                    } else {
                        $escaping = self::ESCAPE_IDENTIFIER;
                    }
                }

                // allow template to contain []
                if ($identifier === '') {
                    $identifier = $nameless_count++;

                    // use rendering only with named tags
                }
                $fx = '_render_' . $identifier;

                // [foo] will attempt to call $this->_render_foo()

                if (array_key_exists($identifier, $this->args['custom'])) {
                    $value = $this->consume($this->args['custom'][$identifier], $escaping);
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

        if (!$hadUnderscoreParamBase) {
            $this->_paramBase = null;
        }

        return trim($res);
    }

    /**
     * Return formatted debug SQL query.
     */
    public function getDebugQuery(): string
    {
        if (func_num_args() > 0) { // remove in 2020-dec
            throw new Exception('Use of $html argument and html rendering has been deprecated');
        }

        $result = $this->render();

        foreach (array_reverse($this->params) as $key => $val) {
            if (is_numeric($key)) {
                continue;
            }

            if (is_numeric($val)) {
                $replacement = $val . '\1';
            } elseif (is_string($val)) {
                $replacement = "'" . addslashes($val) . "'\\1";
            } elseif ($val === null) {
                $replacement = 'NULL\1';
            } else {
                $replacement = $val . '\\1';
            }

            $result = preg_replace('~' . $key . '([^_]|$)~', $replacement, $result);
        }

        if (class_exists('SqlFormatter')) { // requires optional "jdorn/sql-formatter" package
            $result = \SqlFormatter::format($result, false);
        }

        return $result;
    }

    public function __debugInfo()
    {
        $arr = [
            'R' => false,
            'template' => $this->template,
            'params' => $this->params,
            // 'connection' => $this->connection,
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
     * @param DbalConnection|Connection $connection
     *
     * @return \PDOStatement
     */
    public function execute(object $connection = null)
    {
        if ($connection === null) {
            $connection = $this->connection;
        }

        // If it's a DBAL connection, we're cool
        if ($connection instanceof DbalConnection) {
            $query = $this->render();

            try {
                $statement = $connection->prepare($query);

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
                        throw new Exception('Resource type is not supported, set value as string instead');
                    } else {
                        throw (new Exception('Incorrect param type'))
                            ->addMoreInfo('key', $key)
                            ->addMoreInfo('value', $val)
                            ->addMoreInfo('type', gettype($val));
                    }

                    $bind = $statement->bindValue($key, $val, $type);
                    if ($bind === false) {
                        throw (new Exception('Unable to bind parameter'))
                            ->addMoreInfo('param', $key)
                            ->addMoreInfo('value', $val)
                            ->addMoreInfo('type', $type);
                    }
                }

                $statement->execute();
            } catch (DbalException | \Doctrine\DBAL\DBALException $e) { // @phpstan-ignore-line
                $errorInfo = $e->getPrevious() !== null && $e->getPrevious() instanceof \PDOException
                    ? $e->getPrevious()->errorInfo
                    : null;

                $new = (new ExecuteException('DSQL got Exception when executing this query', $errorInfo[1] ?? 0))
                    ->addMoreInfo('error', $errorInfo[2] ?? 'n/a (' . $errorInfo[0] . ')')
                    ->addMoreInfo('query', $this->getDebugQuery());

                throw $new;
            }

            return $statement;
        }

        return $connection->execute($this);
    }

    public function getIterator(): iterable
    {
        return $this->execute();
    }

    // {{{ Result Querying

    /**
     * @param string|int|float|bool|null $v
     */
    private function getCastValue($v): ?string
    {
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        } elseif (is_bool($v)) {
            return $v ? '1' : '0';
        }

        // for Oracle CLOB/BLOB datatypes and PDO driver
        if (is_resource($v) && get_resource_type($v) === 'stream'
                && $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\OraclePlatform) {
            $v = stream_get_contents($v);
        }

        return $v; // throw a type error if not null nor string
    }

    /**
     * @deprecated use "getRows" method instead - will be removed in v2.5
     */
    public function get(): array
    {
        'trigger_error'('Method is deprecated. Use getRows instead', E_USER_DEPRECATED);

        return $this->getRows();
    }

    /**
     * Executes expression and return whole result-set in form of array of hashes.
     *
     * @return string[][]|null[][]
     */
    public function getRows(): array
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
