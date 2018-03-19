<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\dsql;

/**
 * Class for establishing and maintaining connection with your database.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Connection
{
    use \atk4\core\DIContainerTrait;

    /** @var string Query classname */
    protected $query_class = 'atk4\dsql\Query';

    /** @var string Expression classname */
    protected $expression_class = 'atk4\dsql\Expression';

    /** @var Connection|\PDO Connection or PDO object */
    protected $connection = null;

    /** @var int Current depth of transaction */
    public $transaction_depth = 0;

    /**
     * Specifying $properties to constructors will override default
     * property values of this class.
     *
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        if (!is_array($properties)) {
            throw new Exception([
                'Invalid properties for "new Connection()". Did you mean to call Connection::connect()?',
                'properties' => $properties,
            ]);
        }

        $this->setDefaults($properties);
    }

    /**
     * Normalize DSN connection string.
     *
     * Returns normalized DSN as array ['dsn', 'user', 'pass', 'driver', 'rest'].
     *
     * @param array|string $dsn  DSN string
     * @param string       $user Optional username, this takes precedence over dsn string
     * @param string       $pass Optional password, this takes precedence over dsn string
     *
     * @return array
     */
    public static function normalizeDSN($dsn, $user = null, $pass = null)
    {
        // Try to dissect DSN into parts
        $parts = is_array($dsn) ? $dsn : parse_url($dsn);

        // If parts are usable, convert DSN format
        if ($parts !== false && isset($parts['host'], $parts['path'])) {
            // DSN is using URL-like format, so we need to convert it
            $dsn = $parts['scheme'].':host='.$parts['host'].';dbname='.substr($parts['path'], 1);
            $user = $user !== null ? $user : (isset($parts['user']) ? $parts['user'] : null);
            $pass = $pass !== null ? $pass : (isset($parts['pass']) ? $parts['pass'] : null);
        }

        // Find driver
        if (strpos($dsn, ':') === false) {
            throw new Exception([
                "Your DSN format is invalid. Must be in 'driver:host:options' format",
                'dsn' => $dsn,
            ]);
        }
        list($driver, $rest) = explode(':', $dsn, 2);
        $driver = strtolower($driver);

        return ['dsn' => $dsn, 'user' => $user, 'pass' => $pass, 'driver' => $driver, 'rest' => $rest];
    }

    /**
     * Connect database.
     *
     * @param string|\PDO $dsn
     * @param null|string $user
     * @param null|string $password
     * @param array       $args
     *
     * @return Connection
     */
    public static function connect($dsn, $user = null, $password = null, $args = [])
    {
        // If it's already PDO object, then we simply use it
        if ($dsn instanceof \PDO) {
            $driver = $dsn->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $connectionClass = '\\atk4\\dsql\\Connection';
            $queryClass = null;
            $expressionClass = null;
            switch ($driver) {
                case 'pgsql':
                    $connectionClass = '\\atk4\\dsql\\Connection_PgSQL';
                    $queryClass = 'atk4\dsql\Query_PgSQL';
                    break;
                case 'oci':
                    $connectionClass = '\\atk4\\dsql\\Connection_Oracle';
                    break;
                case 'sqlite':
                    $queryClass = 'atk4\dsql\Query_SQLite';
                    break;
                case 'mysql':
                    $expressionClass = 'atk4\dsql\Expression_MySQL';
                default:
                    // Default, for backwards compatibility
                    $queryClass = 'atk4\dsql\Query_MySQL';
                    break;

            }

            return new $connectionClass(array_merge([
                    'connection'       => $dsn,
                    'query_class'      => $queryClass,
                    'expression_class' => $expressionClass,
                ], $args));
        }

        // Process DSN string
        $dsn = static::normalizeDSN($dsn, $user, $password);

        // Create driver specific connection
        switch ($dsn['driver']) {
            case 'mysql':
                $c = new static(array_merge([
                    'connection'       => new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                    'expression_class' => 'atk4\dsql\Expression_MySQL',
                    'query_class'      => 'atk4\dsql\Query_MySQL',
                ], $args));
                break;

            case 'sqlite':
                $c = new static(array_merge([
                    'connection'       => new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                    'query_class'      => 'atk4\dsql\Query_SQLite',
                ], $args));
                break;

            case 'oci':
                $c = new Connection_Oracle(array_merge([
                    'connection' => new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                ], $args));
                break;

            case 'oci12':
                $dsn['dsn'] = str_replace('oci12:', 'oci:', $dsn['dsn']);
                $c = new Connection_Oracle12(array_merge([
                    'connection' => new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                ], $args));
                break;

            case 'pgsql':
                $c = new Connection_PgSQL(array_merge([
                    'connection'       => new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                ], $args));
                break;

            case 'dumper':
                $c = new Connection_Dumper(array_merge([
                    'connection' => static::connect($dsn['rest'], $dsn['user'], $dsn['pass']),
                ], $args));
                break;

            case 'counter':
                $c = new Connection_Counter(array_merge([
                    'connection' => static::connect($dsn['rest'], $dsn['user'], $dsn['pass']),
                ], $args));
                break;

                // let PDO handle the rest
            default:
                $c = new static(array_merge([
                    'connection' => new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass']),
                ], $args));
        }

        return $c;
    }

    /**
     * Returns new Query object with connection already set.
     *
     * @param array $properties
     *
     * @return Query
     */
    public function dsql($properties = [])
    {
        $c = $this->query_class;
        $q = new $c($properties);
        $q->connection = $this;

        return $q;
    }

    /**
     * Returns Expression object with connection already set.
     *
     * @param array $properties
     * @param array $arguments
     *
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
     * Returns Connection or PDO object.
     *
     * @return Connection|\PDO
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * Execute Expression by using this connection.
     *
     * @param Expression $expr
     *
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

    /**
     * Atomic executes operations within one begin/end transaction, so if
     * the code inside callback will fail, then all of the transaction
     * will be also rolled back.
     */
    public function atomic($f)
    {
        $this->beginTransaction();

        try {
            $res = call_user_func($f);
            $this->commit();

            return $res;
        } catch (\Exception $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * Starts new transaction.
     *
     * Database driver supports statements for starting and committing
     * transactions. Unfortunately most of them don't allow to nest
     * transactions and commit gradually.
     * With this method you have some implementation of nested transactions.
     *
     * When you call it for the first time it will begin transaction. If you
     * call it more times, it will do nothing but will increase depth counter.
     * You will need to call commit() for each execution of beginTransactions()
     * and only the last commit will perform actual commit in database.
     *
     * So, if you have been working with the database and got un-handled
     * exception in the middle of your code, everything will be rolled back.
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function beginTransaction()
    {
        // transaction starts only if it was not started before
        $r = $this->inTransaction()
            ? false
            : $this->connection->beginTransaction();

        $this->transaction_depth++;

        return $r;
    }

    /**
     * Will return true if currently running inside a transaction.
     * This is useful if you are logging anything into a database. If you are
     * inside a transaction, don't log or it may be rolled back.
     * Perhaps use a hook for this?
     *
     * @see beginTransaction()
     *
     * @return bool if in transaction
     */
    public function inTransaction()
    {
        return $this->transaction_depth > 0;
    }

    /**
     * Commits transaction.
     *
     * Each occurrence of beginTransaction() must be matched with commit().
     * Only when same amount of commits are executed, the actual commit will be
     * issued to the database.
     *
     * @see beginTransaction()
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function commit()
    {
        // check if transaction is actually started
        if (!$this->inTransaction()) {
            throw new Exception('Using commit() when no transaction has started');
        }

        $this->transaction_depth--;

        if ($this->transaction_depth == 0) {
            return $this->connection->commit();
        }

        return false;
    }

    /**
     * Rollbacks queries since beginTransaction and resets transaction depth.
     *
     * @see beginTransaction()
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function rollBack()
    {
        // check if transaction is actually started
        if (!$this->inTransaction()) {
            throw new Exception('Using rollBack() when no transaction has started');
        }

        $this->transaction_depth--;

        if ($this->transaction_depth == 0) {
            return $this->connection->rollBack();
        }

        return false;
    }

    /**
     * Return last inserted ID value.
     *
     * Few Connection drivers need to receive Model to get ID because PDO doesn't support this method.
     *
     * @param \atk4\data\Model Optional data model from which to return last ID
     *
     * @return mixed
     */
    public function lastInsertID($m = null)
    {
        return $this->connection()->lastInsertID();
    }
}
