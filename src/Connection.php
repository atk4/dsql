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
    protected $query_class = 'atk4\dsql\Query';
    protected $expression_class = 'atk4\dsql\Expression';
    protected $connection = null;

    public static function connect($dsn, $user = null, $password = null)
    {
        list($driver, $rest) = explode(':', $dsn, 2);

        switch (strtolower($driver)) {
            case 'mysql':
                return new Connection([
                    'connection'=>new \PDO($dsn, $user, $password),
                    'query_class'=>'atk4\dsql\Query_MySQL'
                ]);
            case 'sqlite':
                return new Connection([
                    'connection'=>new \PDO($dsn, $user, $password),
                    'query_class'=>'atk4\dsql\Query_SQLite'
                ]);
            case 'dumper':
                return new Connection_Dumper([
                    'connection'=>Connection::connect($rest)
                ]);

            case 'counter':
                return new Connection_Counter([
                    'connection'=>Connection::connect($rest)
                ]);

                // let PDO handle the rest
            default:
                return new Connection([
                    'connection'=>new \PDO($dsn, $user, $password)
                ]);

        }
    }

    /**
     * Specifying $attributes to constructors will override default
     * attribute values of this class.
     *
     * @param array        $attributes
     */
    public function __construct($attributes = null)
    {
        if ($attributes) {
            foreach ($attributes as $key => $val) {
                $this->$key = $val;
            }
        }
    }

    public function dsql($properties = [])
    {
        $c = $this->query_class;
        $q = new $c($properties);
        $q->connection = $this->connection ?: $this;

        return $q;
    }

    public function expr($properties = [], $arguments = null)
    {
        $c = $this->expression_class;
        $e = new $c($properties, $arguments);
        $e->connection = $this->connection ?: $this;

        return $e;
    }

    public function connection()
    {
        return $this->connection;
    }

    public function execute(Expression $expr)
    {
        // If custom connection is set, execute again using that
        if ($this->connection && $this->connection !== $this) {
            return $expr->execute($this->connection);
        }

        throw new Exception('Queries cannot be executed through this connection');
    }
}
