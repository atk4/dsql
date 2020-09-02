<?php

declare(strict_types=1);

namespace atk4\dsql;

/**
 * Class for establishing and maintaining connection with your database.
 */
class Connection
{
    use \atk4\core\DiContainerTrait;

    /** @var string Query classname */
    protected $query_class = Query::class;

    /** @var string Expression classname */
    protected $expression_class = Expression::class;

    /** @var Connection|\PDO Connection or PDO object */
    protected $connection;

    /** @var int Current depth of transaction */
    public $transaction_depth = 0;

    /**
     * Database driver abbreviation, for example mysql, sqlite, pgsql, oci etc.
     * This is filled automatically while connection database.
     *
     * @var string
     */
    public $driverType;

    /**
     * Stores the connectionType => connectionClass array for resolving.
     *
     * @var array
     */
    protected static $connectionClassRegistry = [
        'sqlite' => Sqlite\Connection::class,
        'mysql' => Mysql\Connection::class,
        'pgsql' => Postgresql\Connection::class,
        'oci' => Oracle\Connection::class,
        'stopwatch' => Debug\Stopwatch\Connection::class,
        'profile' => Debug\Profiler\Connection::class,
        'dumper' => Debug\Stopwatch\Connection::class, // backward compatibility - will be removed dec-2020
        'counter' => Debug\Profiler\Connection::class, // backward compatibility - will be removed dec-2020
    ];

    /**
     * Specifying $properties to constructors will override default
     * property values of this class.
     *
     * @param array $properties
     */
    public function __construct($properties = [])
    {
        if (!is_array($properties)) {
            throw (new Exception('Invalid properties for "new Connection()". Did you mean to call Connection::connect()?'))
                ->addMoreInfo('properties', $properties);
        }

        $this->setDefaults($properties);
    }

    /**
     * Normalize DSN connection string.
     *
     * Returns normalized DSN as array ['dsn', 'user', 'pass', 'driverType', 'rest'].
     *
     * @param array|string $dsn  DSN string
     * @param string       $user Optional username, this takes precedence over dsn string
     * @param string       $pass Optional password, this takes precedence over dsn string
     *
     * @return array
     */
    public static function normalizeDsn($dsn, $user = null, $pass = null)
    {
        // Try to dissect DSN into parts
        $parts = is_array($dsn) ? $dsn : parse_url($dsn);

        // If parts are usable, convert DSN format
        if ($parts !== false && isset($parts['host'], $parts['path'])) {
            // DSN is using URL-like format, so we need to convert it
            $dsn = $parts['scheme'] . ':host=' . $parts['host']
                . (isset($parts['port']) ? ';port=' . $parts['port'] : '')
                . ';dbname=' . substr($parts['path'], 1);
            $user = $user ?? ($parts['user'] ?? null);
            $pass = $pass ?? ($parts['pass'] ?? null);
        }

        // If it's still array, then simply use it
        if (is_array($dsn)) {
            return $dsn;
        }

        // If it's string, then find driver
        if (is_string($dsn)) {
            if (strpos($dsn, ':') === false) {
                throw (new Exception('Your DSN format is invalid. Must be in "driverType:host;options" format'))
                    ->addMoreInfo('dsn', $dsn);
            }
            [$driverType, $rest] = explode(':', $dsn, 2);
            $driverType = strtolower($driverType);
        } else {
            // currently impossible to be like this, but we don't want ugly exceptions here
            $driverType = $rest = null;
        }

        return ['dsn' => $dsn, 'user' => $user ?: null, 'pass' => $pass ?: null, 'driverType' => $driverType, 'rest' => $rest];
    }

    /**
     * Connect to database and return connection class.
     *
     * @param string|\PDO $dsn
     * @param string|null $user
     * @param string|null $password
     * @param array       $args
     *
     * @return Connection
     */
    public static function connect($dsn, $user = null, $password = null, $args = [])
    {
        // If it's already PDO object, then we simply use it
        if ($dsn instanceof \PDO) {
            $driverType = $dsn->getAttribute(\PDO::ATTR_DRIVER_NAME);

            $connectionClass = self::resolveConnectionClass($driverType);

            return new $connectionClass(array_merge([
                'connection' => $dsn,
            ], $args));
        }

        // If it's some other object, then we simply use it trough proxy connection
        if (is_object($dsn)) {
            return new ProxyConnection(array_merge([
                'connection' => $dsn,
            ], $args));
        }

        // Process DSN string
        $dsn = static::normalizeDsn($dsn, $user, $password);

        $connectionClass = self::resolveConnectionClass($dsn['driverType']);

        return new $connectionClass(array_merge([
            'connection' => $connectionClass::connectDriver($dsn),
        ], $args));
    }

    /**
     * Adds connection class to the registry for resolving in Connection::resolve method.
     *
     * Can be used as:
     *
     * Connection::registerConnection(MySQL\Connection::class, 'mysql'), or
     * MySQL\Connection::registerConnectionClass()
     *
     * CustomDriver\Connection must be descendant of Connection class.
     *
     * @param string $connectionClass
     * @param string $connectionType
     */
    public static function registerConnectionClass($connectionClass = null, $connectionType = null)
    {
        $connectionClass = $connectionClass ?? static::class;

        $connectionType = $connectionType ?? $connectionClass::defaultDriverType();

        self::$connectionClassRegistry[$connectionType] = $connectionClass;
    }

    /**
     * Resolves the connection class to use based on driver type.
     *
     * @param string $connectionType
     *
     * @return string
     */
    public static function resolveConnectionClass($connectionType)
    {
        return self::$connectionClassRegistry[$connectionType] ?? static::class;
    }

    /**
     * Establishes connection based on a $dsn
     * By default connection is established using new PDO object which can be overridden in child classes.
     *
     * This does not silence PDO errors.
     */
    protected static function connectDriver(array $dsn)
    {
        return new \PDO($dsn['dsn'], $dsn['user'], $dsn['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    /**
     * Returns new Query object with connection already set.
     *
     * @param string|array $properties
     */
    public function dsql($properties = []): Query
    {
        $c = $this->query_class;
        $q = new $c($properties);
        $q->connection = $this;

        return $q;
    }

    /**
     * Returns Expression object with connection already set.
     *
     * @param string|array $properties
     * @param array        $arguments
     */
    public function expr($properties = [], $arguments = null): Expression
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
     * Returns the default driver type set for the connection in $driverType.
     */
    public static function defaultDriverType(): ?string
    {
        return (new \ReflectionClass(static::class))->getDefaultProperties()['driverType'] ?? null;
    }

    /**
     * Execute Expression by using this connection.
     *
     * @return \PDOStatement
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
    public function atomic(\Closure $fx, ...$args)
    {
        $this->beginTransaction();
        try {
            $res = $fx(...$args);
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

        ++$this->transaction_depth;

        return $r;
    }

    /**
     * Will return true if currently running inside a transaction.
     * This is useful if you are logging anything into a database. If you are
     * inside a transaction, don't log or it may be rolled back.
     * Perhaps use a hook for this?
     *
     * @see beginTransaction()
     */
    public function inTransaction(): bool
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

        --$this->transaction_depth;

        if ($this->transaction_depth === 0) {
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

        --$this->transaction_depth;

        if ($this->transaction_depth === 0) {
            return $this->connection->rollBack();
        }

        return false;
    }

    /**
     * Return last inserted ID value.
     *
     * Few Connection drivers need to receive sequence name to get ID because PDO doesn't support this method.
     *
     * @param string $sequence Optional sequence name from which to return last ID
     *
     * @return mixed
     */
    public function lastInsertId(string $sequence = null)
    {
        return $sequence === null ? $this->connection()->lastInsertId() : $this->connection()->lastInsertId($sequence);
    }
}
