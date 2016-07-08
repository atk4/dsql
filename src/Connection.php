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
    /** @var string Query classname */
    protected $query_class = 'atk4\dsql\Query';

    /** @var string Expression classname */
    protected $expression_class = 'atk4\dsql\Expression';

    /** @var Connection Connection object */
    protected $connection = null;

    /** @var int Current depth of transaction */
    public $transaction_depth = 0;

    /**
     * Connect database.
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array  $args
     *
     * @return Connection
     */
    public static function connect($dsn, $user = null, $password = null, $args = [])
    {
        if (strpos($dsn, ':') === false) {
            throw new Exception(["Your DSN format is invalid. Must be in 'driver:host:options' format", 'dsn' => $dsn]);
        }
        list($driver, $rest) = explode(':', $dsn, 2);

        switch (strtolower($driver)) {
            case 'mysql':
                return new self(array_merge([
                    'connection'  => new \PDO($dsn, $user, $password),
                    'query_class' => 'atk4\dsql\Query_MySQL',
                ], $args));
            case 'sqlite':
                return new self(array_merge([
                    'connection'  => new \PDO($dsn, $user, $password),
                    'query_class' => 'atk4\dsql\Query_SQLite',
                ], $args));
            case 'dumper':
                return new Connection_Dumper(array_merge([
                    'connection' => self::connect($rest),
                ], $args));

            case 'counter':
                return new Connection_Counter(array_merge([
                    'connection' => self::connect($rest),
                ], $args));

                // let PDO handle the rest
            default:
                return new self(array_merge([
                    'connection' => new \PDO($dsn, $user, $password),
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
        $q->connection = $this->connection ?: $this;

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
     * Returns Connection object.
     *
     * @return Connection
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
     * Atomic executes operations within one begin/end
     * transaction look, so if the code inside callback
     * will fail, then all of the transaction will be
     * also rolled back.
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
     * Database driver supports statements for starting and committing
     * transactions.
     * Unfortunatelly they don't allow to nest them and commit gradually.
     * With this method you have some implementation of nested transactions.
     *
     * When you call it for the first time it will begin transaction. If you
     * call it more times, it will do nothing but will increase depth counter.
     * You will need to call commit() for each execution of beginTransactions()
     * and the last commit will actually perform a real commit.
     *
     * So if you have been working with the database and got unhandled exception
     * in the middle of your code, everything would be rolled back.
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function beginTransaction()
    {
        ++$this->transaction_depth;
        // transaction starts only if it was not started before
        if ($this->transaction_depth == 1) {
            return $this->connection->beginTransaction();
        }

        return false;
    }

    /**
     * Each occurance of beginTransaction() must be matched with commit().
     * Only when same amount of commits are executed, the ACTUAL commit will be
     * issued to the database.
     *
     * @see beginTransaction()
     *
     * @return mixed Don't rely on any meaningful return
     */
    public function commit()
    {
        --$this->transaction_depth;

        // This means we rolled something back and now we lost track of commits
        if ($this->transaction_depth < 0) {
            $this->transaction_depth = 0;
        }

        if ($this->transaction_depth == 0) {
            return $this->connection->commit();
        }

        return false;
    }

    /**
     * Will return true if currently running inside a transaction.
     * This is useful if you are logging anything into a database. If you are
     * inside a transaction, don't log or it may be rolled back.
     * Perhaps use a hook for this?
     *
     * @see beginTransaction()
     *
     * @return bool if in transactionn
     */
    public function inTransaction()
    {
        return $this->transaction_depth > 0;
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
        --$this->transaction_depth;

        // This means we rolled something back and now we lost track of commits
        if ($this->transaction_depth < 0) {
            $this->transaction_depth = 0;
        }

        if ($this->transaction_depth == 0) {
            return $this->connection->rollBack();
        }

        return false;
    }
}
