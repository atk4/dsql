<?php // vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Class for establishing and maintaining connection with your database.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Connection
{
    /** @var string Query classname */
    protected $query_class = 'atk4\dsql\Query';
    
    /** @var string Expression classname */
    protected $expression_class = 'atk4\dsql\Expression';
    
    /** @var Connection Connection object */
    protected $connection = null;

    /**
     * Connect database
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $args
     * @return Connection
     */
    public static function connect($dsn, $user = null, $password = null, $args = [])
    {
        if (strpos($dsn, ':') === false) {
            throw new Exception(["Your DSN format is invalid. Must be in 'driver:host:options' format", 'dsn'=>$dsn]);
        }
        list($driver, $rest) = explode(':', $dsn, 2);

        switch (strtolower($driver)) {
            case 'mysql':
                return new Connection(array_merge([
                    'connection' => new \PDO($dsn, $user, $password),
                    'query_class' => 'atk4\dsql\Query_MySQL'
                ], $args));
            case 'sqlite':
                return new Connection(array_merge([
                    'connection' => new \PDO($dsn, $user, $password),
                    'query_class' => 'atk4\dsql\Query_SQLite'
                ], $args));
            case 'dumper':
                return new Connection_Dumper(array_merge([
                    'connection' => Connection::connect($rest)
                ], $args));

            case 'counter':
                return new Connection_Counter(array_merge([
                    'connection' => Connection::connect($rest)
                ], $args));

                // let PDO handle the rest
            default:
                return new Connection(array_merge([
                    'connection' => new \PDO($dsn, $user, $password)
                ], $args));

        }
    }

    /**
     * Specifying $attributes to constructors will override default
     * attribute values of this class.
     *
     * @param array $attributes
     */
    public function __construct($attributes = null)
    {
        if ($attributes !== null) {
            if (!is_array($attributes)) {
                throw new Exception('Invalid arguments for "new Connection()". Did you mean to call Connection::connect()?');
            }

            foreach ($attributes as $key => $val) {
                $this->$key = $val;
            }
        }
    }

    /**
     * Returns new Query object with connection already set
     *
     * @param array $properties
     * @return Query
     */
    public function dsql($properties = [])
    {
        $c = $this->query_class;
        $q = new $c($properties);
        $q->connection = $this->connection ?: $this;

        return $q;
    }

    /**
     * Returns Expression object with connection already set
     *
     * @param array $properties
     * @param array $arguments
     * @return Expression
     */
    public function expr($properties = [], $arguments = null)
    {
        $c = $this->expression_class;
        $e = new $c($properties, $arguments);
        $e->connection = $this->connection ?: $this;

        return $e;
    }

    /**
     * Returns Connection object
     *
     * @return Connection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * Execute Expression by using this connection
     *
     * @param Expression $expr
     * @return PDOStatement
     */
    public function execute(Expression $expr)
    {
        // If custom connection is set, execute again using that
        if ($this->connection && $this->connection !== $this) {
            return $expr->execute($this->connection);
        }

        throw new Exception('Queries cannot be executed through this connection');
    }
}
