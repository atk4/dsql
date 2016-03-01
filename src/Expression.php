<?php

namespace atk4\dsql;

/**
 * Implement this interface in your class if it can be used as a part of a
 * query. Implement getDSQLExpression method that would return a valid
 * Query object (or string);
 */
class Expression implements \ArrayAccess {

    /**
     * Creates new expression. Optionally specify a string - a piece
     * of SQL code that will become expression template and arguments.
     *
     * See below for call patterns
     */
    protected $template = null;

    /**
     * Backticks are added around all fields. Set this to blank string to avoid
     */
    protected $escapeChar = '`';

    /**
     * As per PDO, _param() will convert value into :a, :b, :c .. :aa .. etc
     */
    protected $paramBase=':a';

    /**
     * Used for Linking
     * @var [type]
     */
    public $_paramBase=null;

    /**
     * Will be populated with actual values by _param()
     * @var [type]
     */
    public $params=[];


    function __construct($template = null, $arguments = null)
    {
;
        if(is_string($template)){
            $options = ['template' => $template];
        }elseif(is_array($template)){
            $options = $template;
        }else{
            throw new Exception('$template must be a string in Expression::__construct()');
        }

        // new Expression('unix_timestamp([])', [$date]);
        if($arguments){
            if(!is_array($arguments)){
                throw new Exception('$arguments must be an array in Expression::__construct()');
            }
            $this->args['custom'] = $arguments;
        }

        // Deal with remaining options
        foreach ($options as $key => $val) {
            $this->$key = $val;
        }
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->args['custom'][] = $value;
        } else {
            $this->args['custom'][$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->args['custom'][$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->args['custom'][$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->args['custom'][$offset]) ? $this->args['custom'][$offset] : null;
    }


    /**
     * Recursively renders sub-query or expression, combining parameters.
     * If the argument is more likely to be a field, use tick=true
     *
     * @param object|string $dsql Expression
     * @param 'param'|'consume'|'none' $escape_mode Fall-back escaping mode
     *
     * @return string Quoted expression
     */
    protected function _consume($sql_code, $escape_mode = 'param')
    {
        if ($sql_code===null) {
            return null;
        }

        if (!is_object($sql_code)) {
            switch($escape_mode){
                case'param':
                    return $this->_param($sql_code);
                case'escape':
                    return $this->_escape($sql_code);
                case'none':
                    return $sql_code;
            }
        }

        // User may add Expressionable trait to any class, then pass it's objects
        if ($sql_code instanceof Expressionable){
            $sql_code = $sql_code -> getDSQLExpression();
        }

        if (!$sql_code instanceof Expression){
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
        $sql_code->params=[];
        return $ret;
    }

    /**
     * Escapes argument by adding backticks around it.
     * This will allow you to use reserved SQL words as table or field
     * names such as "table
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
     * @param string $val String literal containing input data
     *
     * @return string Safe and escapeed string
     */
    protected function _param($value)
    {
        $name=$this->_paramBase;
        $this->_paramBase++;
        $this->params[$name]=$value;
        return $name;
    }


    public function render()
    {
        $nameless_count = 0;
        if(!isset($this->_paramBase)){
            $this->_paramBase = $this->paramBase;
        }

        $res= preg_replace_callback(
            '/\[([a-z0-9_]*)\]/',
            function ($matches) use (&$nameless_count) {

                // Allow template to contain []
                $identifier = $matches[1];
                if($identifier === ""){
                    $identifier = $nameless_count++;
                }

                // [foo] will attempt to call $this->_render_foo()
                $fx='_render_'.$matches[1];

                if (isset($this->args['custom'][$identifier])) {
                    return $this->_consume($this->args['custom'][$identifier]);
                } elseif (method_exists($this,$fx)) {
                    return $this->$fx();
                } else {
                    throw new Exception('Expression could not render ['.$identifier.']');
                }
            },
                $this->template
            );
        unset($this->_paramBase);
        return $res;
    }

}
